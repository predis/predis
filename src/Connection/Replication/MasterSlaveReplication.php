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
use Predis\ClientException;
use Predis\Command\Command;
use Predis\Command\CommandInterface;
use Predis\Command\RawCommand;
use Predis\Connection\AbstractAggregateConnection;
use Predis\Connection\ConnectionException;
use Predis\Connection\FactoryInterface;
use Predis\Connection\NodeConnectionInterface;
use Predis\Connection\ParametersInterface;
use Predis\Replication\MissingMasterException;
use Predis\Replication\ReplicationStrategy;
use Predis\Response\ErrorInterface as ResponseErrorInterface;

/**
 * Aggregate connection handling replication of Redis nodes configured in a
 * single master / multiple slaves setup.
 */
class MasterSlaveReplication extends AbstractAggregateConnection implements ReplicationInterface
{
    /**
     * @var ReplicationStrategy
     */
    protected $strategy;

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
     * @var NodeConnectionInterface[]
     */
    protected $aliases = [];

    /**
     * @var NodeConnectionInterface
     */
    protected $current;

    /**
     * @var bool
     */
    protected $autoDiscovery = false;

    /**
     * @var FactoryInterface
     */
    protected $connectionFactory;

    /**
     * {@inheritdoc}
     */
    public function __construct(?ReplicationStrategy $strategy = null)
    {
        $this->strategy = $strategy ?: new ReplicationStrategy();
    }

    /**
     * Configures the automatic discovery of the replication configuration on failure.
     *
     * @param bool $value Enable or disable auto discovery.
     */
    public function setAutoDiscovery($value)
    {
        if (!$this->connectionFactory) {
            throw new ClientException('Automatic discovery requires a connection factory');
        }

        $this->autoDiscovery = (bool) $value;
    }

    /**
     * Sets the connection factory used to create the connections by the auto
     * discovery procedure.
     *
     * @param FactoryInterface $connectionFactory Connection factory instance.
     */
    public function setConnectionFactory(FactoryInterface $connectionFactory)
    {
        $this->connectionFactory = $connectionFactory;
    }

    /**
     * Resets the connection state.
     */
    protected function reset()
    {
        $this->current = null;
    }

    /**
     * {@inheritdoc}
     */
    public function add(NodeConnectionInterface $connection)
    {
        $parameters = $connection->getParameters();

        if ('master' === $parameters->role) {
            $this->master = $connection;
        } else {
            // everything else is considered a slvave.
            $this->slaves[] = $connection;
        }

        if (isset($parameters->alias)) {
            $this->aliases[$parameters->alias] = $connection;
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
        } else {
            return false;
        }

        unset($this->pool[(string) $connection]);

        if ($this->aliases && $alias = $connection->getParameters()->alias) {
            unset($this->aliases[$alias]);
        }

        $this->reset();

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getConnectionByCommand(CommandInterface $command)
    {
        if (!$this->current) {
            if ($this->strategy->isReadOperation($command) && $slave = $this->pickSlave()) {
                $this->current = $slave;
            } else {
                $this->current = $this->getMasterOrDie();
            }

            return $this->current;
        }

        if ($this->current === $master = $this->getMasterOrDie()) {
            return $master;
        }

        if (!$this->strategy->isReadOperation($command) || !$this->slaves) {
            $this->current = $master;
        }

        return $this->current;
    }

    /**
     * {@inheritdoc}
     */
    public function getConnectionById($id)
    {
        return $this->pool[$id] ?? null;
    }

    /**
     * Returns a connection instance by its alias.
     *
     * @param string $alias Connection alias.
     *
     * @return NodeConnectionInterface|null
     */
    public function getConnectionByAlias($alias)
    {
        return $this->aliases[$alias] ?? null;
    }

    /**
     * Returns a connection by its role.
     *
     * @param string $role Connection role (`master` or `slave`)
     *
     * @return NodeConnectionInterface|null
     */
    public function getConnectionByRole($role)
    {
        if ($role === 'master') {
            return $this->getMaster();
        } elseif ($role === 'slave') {
            return $this->pickSlave();
        }

        return null;
    }

    /**
     * Switches the internal connection in use by the backend.
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

        $this->current = $connection;
    }

    /**
     * {@inheritdoc}
     */
    public function switchToMaster()
    {
        if (!$connection = $this->getConnectionByRole('master')) {
            throw new InvalidArgumentException('Invalid connection or connection not found.');
        }

        $this->switchTo($connection);
    }

    /**
     * {@inheritdoc}
     */
    public function switchToSlave()
    {
        if (!$connection = $this->getConnectionByRole('slave')) {
            throw new InvalidArgumentException('Invalid connection or connection not found.');
        }

        $this->switchTo($connection);
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
        return $this->master;
    }

    /**
     * Returns the connection associated to the master server.
     *
     * @return NodeConnectionInterface
     */
    private function getMasterOrDie()
    {
        if (!$connection = $this->getMaster()) {
            throw new MissingMasterException('No master server available for replication');
        }

        return $connection;
    }

    /**
     * {@inheritdoc}
     */
    public function getSlaves()
    {
        return $this->slaves;
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
     * Returns a random slave.
     *
     * @return NodeConnectionInterface|null
     */
    protected function pickSlave()
    {
        if (!$this->slaves) {
            return null;
        }

        return $this->slaves[array_rand($this->slaves)];
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
                if (!$this->current = $this->getMaster()) {
                    throw new ClientException('No available connection for replication');
                }
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
     * Handles response from INFO.
     *
     * @param string $response
     *
     * @return array
     */
    private function handleInfoResponse($response)
    {
        $info = [];

        foreach (preg_split('/\r?\n/', $response) as $row) {
            if (strpos($row, ':') === false) {
                continue;
            }

            [$k, $v] = explode(':', $row, 2);
            $info[$k] = $v;
        }

        return $info;
    }

    /**
     * Fetches the replication configuration from one of the servers.
     */
    public function discover()
    {
        if (!$this->connectionFactory) {
            throw new ClientException('Discovery requires a connection factory');
        }

        while (true) {
            try {
                if ($connection = $this->getMaster()) {
                    $this->discoverFromMaster($connection, $this->connectionFactory);
                    break;
                } elseif ($connection = $this->pickSlave()) {
                    $this->discoverFromSlave($connection, $this->connectionFactory);
                    break;
                } else {
                    throw new ClientException('No connection available for discovery');
                }
            } catch (ConnectionException $exception) {
                $this->remove($connection);
            }
        }
    }

    /**
     * Discovers the replication configuration by contacting the master node.
     *
     * @param NodeConnectionInterface $connection        Connection to the master node.
     * @param FactoryInterface        $connectionFactory Connection factory instance.
     */
    protected function discoverFromMaster(NodeConnectionInterface $connection, FactoryInterface $connectionFactory)
    {
        $response = $connection->executeCommand(RawCommand::create('INFO', 'REPLICATION'));
        $replication = $this->handleInfoResponse($response);

        if ($replication['role'] !== 'master') {
            throw new ClientException("Role mismatch (expected master, got slave) [$connection]");
        }

        $this->slaves = [];

        foreach ($replication as $k => $v) {
            $parameters = null;

            if (strpos($k, 'slave') === 0 && preg_match('/ip=(?P<host>.*),port=(?P<port>\d+)/', $v, $parameters)) {
                $slaveConnection = $connectionFactory->create([
                    'host' => $parameters['host'],
                    'port' => $parameters['port'],
                    'role' => 'slave',
                ]);

                $this->add($slaveConnection);
            }
        }
    }

    /**
     * Discovers the replication configuration by contacting one of the slaves.
     *
     * @param NodeConnectionInterface $connection        Connection to one of the slaves.
     * @param FactoryInterface        $connectionFactory Connection factory instance.
     */
    protected function discoverFromSlave(NodeConnectionInterface $connection, FactoryInterface $connectionFactory)
    {
        $response = $connection->executeCommand(RawCommand::create('INFO', 'REPLICATION'));
        $replication = $this->handleInfoResponse($response);

        if ($replication['role'] !== 'slave') {
            throw new ClientException("Role mismatch (expected slave, got master) [$connection]");
        }

        $masterConnection = $connectionFactory->create([
            'host' => $replication['master_host'],
            'port' => $replication['master_port'],
            'role' => 'master',
        ]);

        $this->add($masterConnection);

        $this->discoverFromMaster($masterConnection, $connectionFactory);
    }

    /**
     * Retries the execution of a command upon slave failure.
     *
     * @param CommandInterface $command Command instance.
     * @param string           $method  Actual method.
     *
     * @return mixed
     */
    private function retryCommandOnFailure(CommandInterface $command, $method)
    {
        while (true) {
            try {
                $connection = $this->getConnectionByCommand($command);
                $response = $connection->$method($command);

                if ($response instanceof ResponseErrorInterface && $response->getErrorType() === 'LOADING') {
                    throw new ConnectionException($connection, "Redis is loading the dataset in memory [$connection]");
                }

                break;
            } catch (ConnectionException $exception) {
                $connection = $exception->getConnection();
                $connection->disconnect();

                if ($connection === $this->master && !$this->autoDiscovery) {
                    // Throw immediately when master connection is failing, even
                    // when the command represents a read-only operation, unless
                    // automatic discovery has been enabled.
                    throw $exception;
                } else {
                    // Otherwise remove the failing slave and attempt to execute
                    // the command again on one of the remaining slaves...
                    $this->remove($connection);
                }

                // ... that is, unless we have no more connections to use.
                if (!$this->slaves && !$this->master) {
                    throw $exception;
                } elseif ($this->autoDiscovery) {
                    $this->discover();
                }
            } catch (MissingMasterException $exception) {
                if ($this->autoDiscovery) {
                    $this->discover();
                } else {
                    throw $exception;
                }
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
     * {@inheritdoc}
     */
    public function __sleep()
    {
        return ['master', 'slaves', 'pool', 'aliases', 'strategy'];
    }

    /**
     * {@inheritdoc}
     */
    public function getParameters(): ?ParametersInterface
    {
        if (isset($this->master)) {
            return $this->master->getParameters();
        }

        $slave = $this->pickSlave();

        if (null !== $slave) {
            return $slave->getParameters();
        }

        return null;
    }
}
