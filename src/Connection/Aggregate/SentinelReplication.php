<?php

/*
 * This file is part of the Predis package.
 *
 * (c) Daniele Alessandri <suppakilla@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Connection\Aggregate;

use Predis\Command\CommandInterface;
use Predis\Command\RawCommand;
use Predis\Connection\ConnectionException;
use Predis\Connection\Factory as ConnectionFactory;
use Predis\Connection\FactoryInterface as ConnectionFactoryInterface;
use Predis\Connection\NodeConnectionInterface;
use Predis\Connection\Parameters;
use Predis\Replication\ReplicationStrategy;
use Predis\Response\ErrorInterface as ErrorResponseInterface;
use Predis\Response\ServerException;

/**
 * @author Daniele Alessandri <suppakilla@gmail.com>
 * @author Ville Mattila <ville@eventio.fi>
 */
class SentinelReplication extends MasterSlaveReplication
{
    /**
     * List of sentinel servers.
     */
    protected $sentinels;

    /**
     * Name of the service.
     */
    protected $service;

    /**
     * @var ConnectionFactoryInterface
     */
    protected $connectionFactory;

    /**
     * The current sentinel connection instance.
     *
     * @var NodeConnectionInterface
     */
    protected $sentinelConnection;

    /**
     * Timeout for the connection to a sentinel.
     */
    protected $sentinelTimeout = 0.100;

    /**
     * Flag for automatic retries of commands upon server failure.
     */
    protected $autoRetry = true;

    /**
     * @param array                      $sentinels         Sentinel servers connection parameters.
     * @param string                     $service           Name of the service for autodiscovery.
     * @param ConnectionFactoryInterface $connectionFactory Connection factory instance.
     * @param ReplicationStrategy        $strategy          Replication strategy instance.
     */
    public function __construct(
        array $sentinels,
        $service,
        ConnectionFactoryInterface $connectionFactory,
        ReplicationStrategy $strategy = null
    ) {
        $this->sentinels = $sentinels;
        $this->service = $service;
        $this->connectionFactory = $connectionFactory;

        parent::__construct($strategy);
    }

    /**
     * Sets a default timeout for connections to sentinels.
     *
     * When "timeout" is present in the connection parameters of sentinels, its
     * value overrides the default sentinel timeout.
     *
     * @param float $timeout Timeout value.
     */
    public function setDefaultSentinelTimeout($timeout)
    {
        $this->sentinelTimeout = (float) $timeout;
    }

    /**
     * Set automatic retries of commands upon server failure.
     *
     * @param bool $retry Retry value.
     */
    public function setAutomaticRetry($retry)
    {
        $this->autoRetry = (bool) $retry;
    }

    /**
     * {@inheritdoc}
     */
    protected function check()
    {
        $this->querySentinel();

        parent::check();
    }

    /**
     * Wipes the list of master and slaves nodes.
     */
    protected function wipeServerList()
    {
        $this->reset();

        $this->master = null;
        $this->slaves = null;
    }

    /**
     * Creates the connection to a sentinel server.
     *
     * @return NodeConnectionInterface
     */
    protected function createSentinelConnection($parameters)
    {
        if ($parameters instanceof NodeConnectionInterface) {
            return $parameters;
        }

        if (is_string($parameters)) {
            $parameters = Parameters::parse($parameters);
        }

        if (is_array($parameters)) {
            $parameters += array(
                'timeout' => $this->sentinelTimeout,
            );
        }

        $connection = $this->connectionFactory->create($parameters);

        return $connection;
    }

    /**
     * Returns the current sentinel connection.
     *
     * If there is no active sentinel connection, a new connection is created.
     *
     * @return NodeConnectionInterface
     */
    public function getSentinelConnection()
    {
        if (!$this->sentinelConnection) {
            if (!$this->sentinels) {
                throw new \Predis\ClientException('No sentinel server available for autodiscovery.');
            }

            $sentinel = array_shift($this->sentinels);
            $this->sentinelConnection = $this->createSentinelConnection($sentinel);
        }

        return $this->sentinelConnection;
    }

    /**
     * Fetches details of the master server from a sentinel server.
     *
     * @param NodeConnectionInterface $sentinel Connection to a sentinel server.
     * @param string                  $service  Name of the service.
     *
     * @return array
     */
    protected function getMasterFromSentinel(NodeConnectionInterface $sentinel, $service)
    {
        $payload = $sentinel->executeCommand(
            RawCommand::create('SENTINEL', 'get-master-addr-by-name', $service)
        );

        if ($payload === null) {
            throw new ServerException('ERR No such master with that name');
        }

        return array(
            'host' => $payload[0],
            'port' => $payload[1],
            'alias' => 'master',
        );
    }

    /**
     * Fetches details of the slave servers from a sentinel server.
     *
     * @param NodeConnectionInterface $sentinel Connection to a sentinel server.
     * @param string                  $service  Name of the service.
     *
     * @return array
     */
    protected function getSlavesFromSentinel(NodeConnectionInterface $sentinel, $service)
    {
        $slaves = array();

        $payload = $sentinel->executeCommand(
            RawCommand::create('SENTINEL', 'slaves', $service)
        );

        if ($payload instanceof ErrorResponseInterface) {
            throw new ServerException($response->getMessage());
        }

        foreach ($payload as $slave) {
            $flags = explode(',', $slave[9]);

            if (array_intersect($flags, array('s_down', 'disconnected'))) {
                continue;
            }

            $slaves[] = array(
                'host' => $slave[3],
                'port' => $slave[5],
            );
        }

        return $slaves;
    }

    /**
     * Configures replication by querying a sentinel server for autodiscovery.
     */
    public function querySentinel()
    {
        $this->wipeServerList();

        SENTINEL_QUERY: {
            $sentinel = $this->getSentinelConnection();

            try {
                $masterParameters = $this->getMasterFromSentinel($sentinel, $this->service);
                $masterConnection = $this->connectionFactory->create($masterParameters);
                $this->add($masterConnection);

                if (!$slavesParameters = $this->getSlavesFromSentinel($sentinel, $this->service)) {
                    unset($masterParameters['alias']);
                    $slavesParameters[] = $masterParameters;
                }

                foreach ($slavesParameters as $slaveParameters) {
                    $this->add($this->connectionFactory->create($slaveParameters));
                }
            } catch (ConnectionException $exception) {
                $this->sentinelConnection = null;

                goto SENTINEL_QUERY;
            }
        }
    }

    /**
     * Retries the execution of a command upon server failure after asking a new
     * configuration to one of the sentinels.
     *
     * @param string           $method  Actual method.
     * @param CommandInterface $command Command instance.
     *
     * @return mixed
     */
    private function retryCommandOnFailure($method, $command)
    {
        SENTINEL_RETRY: {
            try {
                $response = parent::$method($command);
            } catch (ConnectionException $exception) {
                if (!$this->autoRetry) {
                    throw $exception;
                }

                $exception->getConnection()->disconnect();
                $this->querySentinel();

                goto SENTINEL_RETRY;
            }
        }

        return $response;
    }

    /**
     * {@inheritdoc}
     */
    public function writeRequest(CommandInterface $command)
    {
        $this->retryCommandOnFailure(__FUNCTION__, $command);
    }

    /**
     * {@inheritdoc}
     */
    public function readResponse(CommandInterface $command)
    {
        return $this->retryCommandOnFailure(__FUNCTION__, $command);
    }

    /**
     * {@inheritdoc}
     */
    public function executeCommand(CommandInterface $command)
    {
        return $this->retryCommandOnFailure(__FUNCTION__, $command);
    }
}
