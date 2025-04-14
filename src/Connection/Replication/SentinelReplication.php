<?php

/*
 * This file is part of the Predis package.
 *
 * (c) 2009-2020 Daniele Alessandri
 * (c) 2021-2025 Till Krüss
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Connection\Replication;

use InvalidArgumentException;
use Predis\Command\Command;
use Predis\Command\CommandInterface;
use Predis\Command\RawCommand;
use Predis\CommunicationException;
use Predis\Connection\AbstractAggregateConnection;
use Predis\Connection\ConnectionException;
use Predis\Connection\FactoryInterface as ConnectionFactoryInterface;
use Predis\Connection\NodeConnectionInterface;
use Predis\Connection\Parameters;
use Predis\Connection\ParametersInterface;
use Predis\Replication\ReplicationStrategy;
use Predis\Replication\RoleException;
use Predis\Response\Error;
use Predis\Response\ErrorInterface as ErrorResponseInterface;
use Predis\Response\ServerException;

/**
 * @author Daniele Alessandri <suppakilla@gmail.com>
 * @author Ville Mattila <ville@eventio.fi>
 */
class SentinelReplication extends AbstractAggregateConnection implements ReplicationInterface
{
    /**
     * @var NodeConnectionInterface
     */
    protected $master;

    /**
     * @var NodeConnectionInterface[]
     */
    protected $slaves = [];

    /**
     * @var NodeConnectionInterface[]
     */
    protected $pool = [];

    /**
     * @var NodeConnectionInterface
     */
    protected $current;

    /**
     * @var string
     */
    protected $service;

    /**
     * @var ConnectionFactoryInterface
     */
    protected $connectionFactory;

    /**
     * @var ReplicationStrategy
     */
    protected $strategy;

    /**
     * @var NodeConnectionInterface[]
     */
    protected $sentinels = [];

    /**
     * @var int
     */
    protected $sentinelIndex = 0;

    /**
     * @var NodeConnectionInterface
     */
    protected $sentinelConnection;

    /**
     * @var float
     */
    protected $sentinelTimeout = 0.100;

    /**
     * Max number of automatic retries of commands upon server failure.
     *
     * -1 = unlimited retry attempts
     *  0 = no retry attempts (fails immediately)
     *  n = fail only after n retry attempts
     *
     * @var int
     */
    protected $retryLimit = 20;

    /**
     * Time to wait in milliseconds before fetching a new configuration from one
     * of the sentinel servers.
     *
     * @var int
     */
    protected $retryWait = 1000;

    /**
     * Flag for automatic fetching of available sentinels.
     *
     * @var bool
     */
    protected $updateSentinels = false;

    /**
     * @param string                     $service           Name of the service for autodiscovery.
     * @param array                      $sentinels         Sentinel servers connection parameters.
     * @param ConnectionFactoryInterface $connectionFactory Connection factory instance.
     * @param ReplicationStrategy|null   $strategy          Replication strategy instance.
     */
    public function __construct(
        $service,
        array $sentinels,
        ConnectionFactoryInterface $connectionFactory,
        ?ReplicationStrategy $strategy = null
    ) {
        $this->sentinels = $sentinels;
        $this->service = $service;
        $this->connectionFactory = $connectionFactory;
        $this->strategy = $strategy ?: new ReplicationStrategy();
    }

    /**
     * Sets a default timeout for connections to sentinels.
     *
     * When "timeout" is present in the connection parameters of sentinels, its
     * value overrides the default sentinel timeout.
     *
     * @param float $timeout Timeout value.
     */
    public function setSentinelTimeout($timeout)
    {
        $this->sentinelTimeout = (float) $timeout;
    }

    /**
     * Sets the maximum number of retries for commands upon server failure.
     *
     * -1 = unlimited retry attempts
     *  0 = no retry attempts (fails immediately)
     *  n = fail only after n retry attempts
     *
     * @param int $retry Number of retry attempts.
     */
    public function setRetryLimit($retry)
    {
        $this->retryLimit = (int) $retry;
    }

    /**
     * Sets the time to wait (in milliseconds) before fetching a new configuration
     * from one of the sentinels.
     *
     * @param float $milliseconds Time to wait before the next attempt.
     */
    public function setRetryWait($milliseconds)
    {
        $this->retryWait = (float) $milliseconds;
    }

    /**
     * Set automatic fetching of available sentinels.
     *
     * @param bool $update Enable or disable automatic updates.
     */
    public function setUpdateSentinels($update)
    {
        $this->updateSentinels = (bool) $update;
    }

    /**
     * Resets the current connection.
     */
    protected function reset()
    {
        $this->current = null;
    }

    /**
     * Wipes the current list of master and slaves nodes.
     */
    protected function wipeServerList()
    {
        $this->reset();

        $this->master = null;
        $this->slaves = [];
        $this->pool = [];
    }

    /**
     * {@inheritdoc}
     */
    public function add(NodeConnectionInterface $connection)
    {
        $parameters = $connection->getParameters();
        $role = $parameters->role;

        if ('master' === $role) {
            $this->master = $connection;
        } elseif ('sentinel' === $role) {
            $this->sentinels[] = $connection;

            // sentinels are not considered part of the pool.
            return;
        } else {
            // everything else is considered a slave.
            $this->slaves[] = $connection;
        }

        $this->pool[(string) $connection] = $connection;

        $this->reset();
    }

    /**
     * {@inheritdoc}
     */
    public function remove(NodeConnectionInterface $connection)
    {
        if ($connection === $this->master) {
            $this->master = null;
        } elseif (false !== $id = array_search($connection, $this->slaves, true)) {
            unset($this->slaves[$id]);
        } elseif (false !== $id = array_search($connection, $this->sentinels, true)) {
            unset($this->sentinels[$id]);

            return true;
        } else {
            return false;
        }

        unset($this->pool[(string) $connection]);

        $this->reset();

        return true;
    }

    /**
     * Creates a new connection to a sentinel server.
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
            // NOTE: sentinels do not accept SELECT command so we must
            // explicitly set it to NULL to avoid problems when using default
            // parameters set via client options.
            $parameters['database'] = null;

            // don't leak password from between configurations
            // https://github.com/predis/predis/pull/807/#discussion_r985764770
            if (!isset($parameters['password'])) {
                $parameters['password'] = null;
            }

            if (!isset($parameters['timeout'])) {
                $parameters['timeout'] = $this->sentinelTimeout;
            }
        }

        return $this->connectionFactory->create($parameters);
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
            if ($this->sentinelIndex >= count($this->sentinels)) {
                $this->sentinelIndex = 0;
                throw new \Predis\ClientException('No sentinel server available for autodiscovery.');
            }

            $sentinel = $this->sentinels[$this->sentinelIndex];
            ++$this->sentinelIndex;
            $this->sentinelConnection = $this->createSentinelConnection($sentinel);
        }

        return $this->sentinelConnection;
    }

    /**
     * Fetches an updated list of sentinels from a sentinel.
     */
    public function updateSentinels()
    {
        SENTINEL_QUERY: {
            $sentinel = $this->getSentinelConnection();

            try {
                $payload = $sentinel->executeCommand(
                    RawCommand::create('SENTINEL', 'sentinels', $this->service)
                );

                $this->sentinels = [];
                $this->sentinelIndex = 0;
                // NOTE: sentinel server does not return itself, so we add it back.
                $this->sentinels[] = $sentinel->getParameters()->toArray();

                foreach ($payload as $sentinel) {
                    $this->sentinels[] = [
                        'host' => $sentinel[3],
                        'port' => $sentinel[5],
                        'role' => 'sentinel',
                    ];
                }
            } catch (ConnectionException $exception) {
                $this->sentinelConnection = null;

                goto SENTINEL_QUERY;
            }
        }
    }

    /**
     * Fetches the details for the master and slave servers from a sentinel.
     */
    public function querySentinel()
    {
        $this->wipeServerList();

        $this->updateSentinels();
        $this->getMaster();
        $this->getSlaves();
    }

    /**
     * Handles error responses returned by redis-sentinel.
     *
     * @param NodeConnectionInterface $sentinel Connection to a sentinel server.
     * @param ErrorResponseInterface  $error    Error response.
     */
    private function handleSentinelErrorResponse(NodeConnectionInterface $sentinel, ErrorResponseInterface $error)
    {
        if ($error->getErrorType() === 'IDONTKNOW') {
            throw new ConnectionException($sentinel, $error->getMessage());
        } else {
            throw new ServerException($error->getMessage());
        }
    }

    /**
     * Fetches the details for the master server from a sentinel.
     *
     * @param NodeConnectionInterface $sentinel Connection to a sentinel server.
     * @param string                  $service  Name of the service.
     *
     * @return array
     */
    protected function querySentinelForMaster(NodeConnectionInterface $sentinel, $service)
    {
        $payload = $sentinel->executeCommand(
            RawCommand::create('SENTINEL', 'get-master-addr-by-name', $service)
        );

        if ($payload === null) {
            throw new ServerException('ERR No such master with that name');
        }

        if ($payload instanceof ErrorResponseInterface) {
            $this->handleSentinelErrorResponse($sentinel, $payload);
        }

        return [
            'host' => $payload[0],
            'port' => $payload[1],
            'role' => 'master',
        ];
    }

    /**
     * Fetches the details for the slave servers from a sentinel.
     *
     * @param NodeConnectionInterface $sentinel Connection to a sentinel server.
     * @param string                  $service  Name of the service.
     *
     * @return array
     */
    protected function querySentinelForSlaves(NodeConnectionInterface $sentinel, $service)
    {
        $slaves = [];

        $payload = $sentinel->executeCommand(
            RawCommand::create('SENTINEL', 'slaves', $service)
        );

        if ($payload instanceof ErrorResponseInterface) {
            $this->handleSentinelErrorResponse($sentinel, $payload);
        }

        foreach ($payload as $slave) {
            $flags = explode(',', $slave[9]);

            if (array_intersect($flags, ['s_down', 'o_down', 'disconnected'])) {
                continue;
            }

            // ensure `master-link-status` is ok
            if (isset($slave[31]) && $slave[31] === 'err') {
                continue;
            }

            $slaves[] = [
                'host' => $slave[3],
                'port' => $slave[5],
                'role' => 'slave',
            ];
        }

        return $slaves;
    }

    /**
     * {@inheritdoc}
     */
    public function getCurrent()
    {
        return $this->current;
    }

    /**
     * {@inheritdoc}
     */
    public function getMaster()
    {
        if ($this->master) {
            return $this->master;
        }

        if ($this->updateSentinels) {
            $this->updateSentinels();
        }

        SENTINEL_QUERY: {
            $sentinel = $this->getSentinelConnection();

            try {
                $masterParameters = $this->querySentinelForMaster($sentinel, $this->service);
                $masterConnection = $this->connectionFactory->create($masterParameters);

                $this->add($masterConnection);
            } catch (ConnectionException $exception) {
                $this->sentinelConnection = null;

                goto SENTINEL_QUERY;
            }
        }

        return $masterConnection;
    }

    /**
     * {@inheritdoc}
     */
    public function getSlaves()
    {
        if ($this->slaves) {
            return array_values($this->slaves);
        }

        if ($this->updateSentinels) {
            $this->updateSentinels();
        }

        SENTINEL_QUERY: {
            $sentinel = $this->getSentinelConnection();

            try {
                $slavesParameters = $this->querySentinelForSlaves($sentinel, $this->service);

                foreach ($slavesParameters as $slaveParameters) {
                    $this->add($this->connectionFactory->create($slaveParameters));
                }
            } catch (ConnectionException $exception) {
                $this->sentinelConnection = null;

                goto SENTINEL_QUERY;
            }
        }

        return array_values($this->slaves);
    }

    /**
     * Returns a random slave.
     *
     * @return NodeConnectionInterface|null
     */
    protected function pickSlave()
    {
        $slaves = $this->getSlaves();

        return $slaves
            ? $slaves[rand(1, count($slaves)) - 1]
            : null;
    }

    /**
     * Returns the connection instance in charge for the given command.
     *
     * @param CommandInterface $command Command instance.
     *
     * @return NodeConnectionInterface
     */
    private function getConnectionInternal(CommandInterface $command)
    {
        if (!$this->current) {
            if ($this->strategy->isReadOperation($command) && $slave = $this->pickSlave()) {
                $this->current = $slave;
            } else {
                $this->current = $this->getMaster();
            }

            return $this->current;
        }

        if ($this->current === $this->master) {
            return $this->current;
        }

        if (!$this->strategy->isReadOperation($command)) {
            $this->current = $this->getMaster();
        }

        return $this->current;
    }

    /**
     * Asserts that the specified connection matches an expected role.
     *
     * @param NodeConnectionInterface $connection Connection to a redis server.
     * @param string                  $role       Expected role of the server ("master", "slave" or "sentinel").
     *
     * @throws RoleException|ConnectionException
     */
    protected function assertConnectionRole(NodeConnectionInterface $connection, $role)
    {
        $role = strtolower($role);
        $actualRole = $connection->executeCommand(RawCommand::create('ROLE'));

        if ($actualRole instanceof Error) {
            throw new ConnectionException($connection, $actualRole->getMessage());
        }

        if ($role !== $actualRole[0]) {
            throw new RoleException($connection, "Expected $role but got $actualRole[0] [$connection]");
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getConnectionByCommand(CommandInterface $command)
    {
        $connection = $this->getConnectionInternal($command);

        if (!$connection->isConnected()) {
            // When we do not have any available slave in the pool we can expect
            // read-only operations to hit the master server.
            $expectedRole = $this->strategy->isReadOperation($command) && $this->slaves ? 'slave' : 'master';
            $this->assertConnectionRole($connection, $expectedRole);
        }

        return $connection;
    }

    /**
     * {@inheritdoc}
     */
    public function getConnectionById($id)
    {
        return $this->pool[$id] ?? null;
    }

    /**
     * Returns a connection by its role.
     *
     * @param string $role Connection role (`master`, `slave` or `sentinel`)
     *
     * @return NodeConnectionInterface|null
     */
    public function getConnectionByRole($role)
    {
        if ($role === 'master') {
            return $this->getMaster();
        } elseif ($role === 'slave') {
            return $this->pickSlave();
        } elseif ($role === 'sentinel') {
            return $this->getSentinelConnection();
        } else {
            return null;
        }
    }

    /**
     * Switches the internal connection in use by the backend.
     *
     * Sentinel connections are not considered as part of the pool, meaning that
     * trying to switch to a sentinel will throw an exception.
     *
     * @param NodeConnectionInterface $connection Connection instance in the pool.
     */
    public function switchTo(NodeConnectionInterface $connection)
    {
        if ($connection && $connection === $this->current) {
            return;
        }

        if ($connection !== $this->master && !in_array($connection, $this->slaves, true)) {
            throw new InvalidArgumentException('Invalid connection or connection not found.');
        }

        $connection->connect();

        if ($this->current) {
            $this->current->disconnect();
        }

        $this->current = $connection;
    }

    /**
     * {@inheritdoc}
     */
    public function switchToMaster()
    {
        $connection = $this->getConnectionByRole('master');
        $this->switchTo($connection);
    }

    /**
     * {@inheritdoc}
     */
    public function switchToSlave()
    {
        $connection = $this->getConnectionByRole('slave');
        $this->switchTo($connection);
    }

    /**
     * {@inheritdoc}
     */
    public function isConnected()
    {
        return $this->current ? $this->current->isConnected() : false;
    }

    /**
     * {@inheritdoc}
     */
    public function connect()
    {
        if (!$this->current) {
            if (!$this->current = $this->pickSlave()) {
                $this->current = $this->getMaster();
            }
        }

        $this->current->connect();
    }

    /**
     * {@inheritdoc}
     */
    public function disconnect()
    {
        foreach ($this->pool as $connection) {
            $connection->disconnect();
        }
    }

    /**
     * Retries the execution of a command upon server failure after asking a new
     * configuration to one of the sentinels.
     *
     * @param CommandInterface $command Command instance.
     * @param string           $method  Actual method.
     *
     * @return mixed
     */
    private function retryCommandOnFailure(CommandInterface $command, $method)
    {
        $retries = 0;

        while ($retries <= $this->retryLimit) {
            try {
                $response = $this->getConnectionByCommand($command)->$method($command);
                break;
            } catch (CommunicationException $exception) {
                $this->wipeServerList();
                $exception->getConnection()->disconnect();

                if ($retries === $this->retryLimit) {
                    throw $exception;
                }

                usleep($this->retryWait * 1000);

                ++$retries;
            }
        }

        return $response;
    }

    /**
     * {@inheritdoc}
     */
    public function writeRequest(CommandInterface $command)
    {
        $this->retryCommandOnFailure($command, __FUNCTION__);
    }

    /**
     * {@inheritdoc}
     */
    public function readResponse(CommandInterface $command)
    {
        return $this->retryCommandOnFailure($command, __FUNCTION__);
    }

    /**
     * {@inheritdoc}
     */
    public function executeCommand(CommandInterface $command)
    {
        return $this->retryCommandOnFailure($command, __FUNCTION__);
    }

    /**
     * Returns the underlying replication strategy.
     *
     * @return ReplicationStrategy
     */
    public function getReplicationStrategy()
    {
        return $this->strategy;
    }

    /**
     * {@inheritdoc}
     */
    public function __sleep()
    {
        return [
            'master', 'slaves', 'pool', 'service', 'sentinels', 'connectionFactory', 'strategy',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getParameters(): ?ParametersInterface
    {
        if (isset($this->master)) {
            return $this->master->getParameters();
        }

        if (!empty($this->slaves)) {
            return $this->slaves[0]->getParameters();
        }

        if (!empty($this->sentinels)) {
            return $this->sentinels[0]->getParameters();
        }

        return null;
    }
}
