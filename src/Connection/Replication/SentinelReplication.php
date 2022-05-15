<?php

/*
 * This file is part of the Predis package.
 *
 * (c) Daniele Alessandri <suppakilla@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Connection\Replication;

use Predis\Command\CommandInterface;
use Predis\Command\RawCommand;
use Predis\CommunicationException;
use Predis\Connection\ConnectionException;
use Predis\Connection\FactoryInterface as ConnectionFactoryInterface;
use Predis\Connection\NodeConnectionInterface;
use Predis\Connection\Parameters;
use Predis\Replication\ReplicationStrategy;
use Predis\Replication\RoleException;
use Predis\Response\ErrorInterface as ErrorResponseInterface;
use Predis\Response\ServerException;

/**
 * @author Daniele Alessandri <suppakilla@gmail.com>
 * @author Ville Mattila <ville@eventio.fi>
 */
class SentinelReplication implements ReplicationInterface
{
    /**
     * @var NodeConnectionInterface
     */
    protected $master;

    /**
     * @var NodeConnectionInterface[]
     */
    protected $slaves = array();

    /**
     * @var NodeConnectionInterface[]
     */
    protected $pool = array();

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
    protected $sentinels = array();

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
     *  0 = no retry attempts (fails immediatly)
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
     * @param string                     $service           name of the service for autodiscovery
     * @param array                      $sentinels         sentinel servers connection parameters
     * @param ConnectionFactoryInterface $connectionFactory connection factory instance
     * @param ReplicationStrategy        $strategy          replication strategy instance
     */
    public function __construct(
        $service,
        array $sentinels,
        ConnectionFactoryInterface $connectionFactory,
        ReplicationStrategy $strategy = null
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
     * @param float $timeout timeout value
     */
    public function setSentinelTimeout($timeout)
    {
        $this->sentinelTimeout = (float) $timeout;
    }

    /**
     * Sets the maximum number of retries for commands upon server failure.
     *
     * -1 = unlimited retry attempts
     *  0 = no retry attempts (fails immediatly)
     *  n = fail only after n retry attempts
     *
     * @param int $retry number of retry attempts
     */
    public function setRetryLimit($retry)
    {
        $this->retryLimit = (int) $retry;
    }

    /**
     * Sets the time to wait (in seconds) before fetching a new configuration
     * from one of the sentinels.
     *
     * @param float $seconds time to wait before the next attempt
     */
    public function setRetryWait($seconds)
    {
        $this->retryWait = (float) $seconds;
    }

    /**
     * Set automatic fetching of available sentinels.
     *
     * @param bool $update enable or disable automatic updates
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
        $this->slaves = array();
        $this->pool = array();
    }

    /**
     * {@inheritdoc}
     */
    public function add(NodeConnectionInterface $connection)
    {
        $parameters = $connection->getParameters();

        if ('master' === $role = $parameters->role) {
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
            // Password authentication is fine now that Redis Sentinel supports
            // password-protected sentinel instances, but we must explicitly set
            // "database" and "username" to NULL so that no augmented AUTH (ACL)
            // and SELECT command are sent by accident to the sentinels.
            $parameters['database'] = null;
            $parameters['username'] = null;

            if (!isset($parameters['timeout'])) {
                $parameters['timeout'] = $this->sentinelTimeout;
            }
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
     * Fetches an updated list of sentinels from a sentinel.
     */
    public function updateSentinels()
    {
        SENTINEL_QUERY:
            $sentinel = $this->getSentinelConnection();

        try {
            $payload = $sentinel->executeCommand(
                    RawCommand::create('SENTINEL', 'sentinels', $this->service)
                );

            $this->sentinels = array();
            // NOTE: sentinel server does not return itself, so we add it back.
            $this->sentinels[] = $sentinel->getParameters()->toArray();

            foreach ($payload as $sentinel) {
                $this->sentinels[] = array(
                        'host' => $sentinel[3],
                        'port' => $sentinel[5],
                        'role' => 'sentinel',
                    );
            }
        } catch (ConnectionException $exception) {
            $this->sentinelConnection = null;

            goto SENTINEL_QUERY;
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
     * @param NodeConnectionInterface $sentinel connection to a sentinel server
     * @param ErrorResponseInterface  $error    error response
     */
    private function handleSentinelErrorResponse(NodeConnectionInterface $sentinel, ErrorResponseInterface $error)
    {
        if ('IDONTKNOW' === $error->getErrorType()) {
            throw new ConnectionException($sentinel, $error->getMessage());
        } else {
            throw new ServerException($error->getMessage());
        }
    }

    /**
     * Fetches the details for the master server from a sentinel.
     *
     * @param NodeConnectionInterface $sentinel connection to a sentinel server
     * @param string                  $service  name of the service
     *
     * @return array
     */
    protected function querySentinelForMaster(NodeConnectionInterface $sentinel, $service)
    {
        $payload = $sentinel->executeCommand(
            RawCommand::create('SENTINEL', 'get-master-addr-by-name', $service)
        );

        if (null === $payload) {
            throw new ServerException('ERR No such master with that name');
        }

        if ($payload instanceof ErrorResponseInterface) {
            $this->handleSentinelErrorResponse($sentinel, $payload);
        }

        return array(
            'host' => $payload[0],
            'port' => $payload[1],
            'role' => 'master',
        );
    }

    /**
     * Fetches the details for the slave servers from a sentinel.
     *
     * @param NodeConnectionInterface $sentinel connection to a sentinel server
     * @param string                  $service  name of the service
     *
     * @return array
     */
    protected function querySentinelForSlaves(NodeConnectionInterface $sentinel, $service)
    {
        $slaves = array();

        $payload = $sentinel->executeCommand(
            RawCommand::create('SENTINEL', 'slaves', $service)
        );

        if ($payload instanceof ErrorResponseInterface) {
            $this->handleSentinelErrorResponse($sentinel, $payload);
        }

        foreach ($payload as $slave) {
            $flags = explode(',', $slave[9]);

            if (array_intersect($flags, array('s_down', 'o_down', 'disconnected'))) {
                continue;
            }

            $slaves[] = array(
                'host' => $slave[3],
                'port' => $slave[5],
                'role' => 'slave',
            );
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

        SENTINEL_QUERY:
            $sentinel = $this->getSentinelConnection();

        try {
            $masterParameters = $this->querySentinelForMaster($sentinel, $this->service);
            $masterConnection = $this->connectionFactory->create($masterParameters);

            $this->add($masterConnection);
        } catch (ConnectionException $exception) {
            $this->sentinelConnection = null;

            goto SENTINEL_QUERY;
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

        SENTINEL_QUERY:
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

        return array_values($this->slaves);
    }

    /**
     * Returns a random slave.
     *
     * @return NodeConnectionInterface
     */
    protected function pickSlave()
    {
        if ($slaves = $this->getSlaves()) {
            return $slaves[rand(1, count($slaves)) - 1];
        }
    }

    /**
     * Returns the connection instance in charge for the given command.
     *
     * @param CommandInterface $command command instance
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
     * @param NodeConnectionInterface $connection connection to a redis server
     * @param string                  $role       expected role of the server ("master", "slave" or "sentinel")
     *
     * @throws RoleException
     */
    protected function assertConnectionRole(NodeConnectionInterface $connection, $role)
    {
        $role = strtolower($role);
        $actualRole = $connection->executeCommand(RawCommand::create('ROLE'));

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
        if (isset($this->pool[$id])) {
            return $this->pool[$id];
        }
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
        if ('master' === $role) {
            return $this->getMaster();
        } elseif ('slave' === $role) {
            return $this->pickSlave();
        } elseif ('sentinel' === $role) {
            return $this->getSentinelConnection();
        }
    }

    /**
     * Switches the internal connection in use by the backend.
     *
     * Sentinel connections are not considered as part of the pool, meaning that
     * trying to switch to a sentinel will throw an exception.
     *
     * @param NodeConnectionInterface $connection connection instance in the pool
     */
    public function switchTo(NodeConnectionInterface $connection)
    {
        if ($connection && $connection === $this->current) {
            return;
        }

        if ($connection !== $this->master && !in_array($connection, $this->slaves, true)) {
            throw new \InvalidArgumentException('Invalid connection or connection not found.');
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
     * @param CommandInterface $command command instance
     * @param string           $method  actual method
     *
     * @return mixed
     */
    private function retryCommandOnFailure(CommandInterface $command, $method)
    {
        $retries = 0;

        SENTINEL_RETRY:
            try {
                $response = $this->getConnectionByCommand($command)->$method($command);
            } catch (CommunicationException $exception) {
                $this->wipeServerList();
                $exception->getConnection()->disconnect();

                if ($retries == $this->retryLimit) {
                    throw $exception;
                }

                usleep($this->retryWait * 1000);

                ++$retries;
                goto SENTINEL_RETRY;
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
        return array(
            'master', 'slaves', 'pool', 'service', 'sentinels', 'connectionFactory', 'strategy',
        );
    }
}
