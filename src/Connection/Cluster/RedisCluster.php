<?php

/*
 * This file is part of the Predis package.
 *
 * (c) Daniele Alessandri <suppakilla@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Connection\Cluster;

use Predis\ClientException;
use Predis\Cluster\RedisStrategy as RedisClusterStrategy;
use Predis\Cluster\SlotMap;
use Predis\Cluster\StrategyInterface;
use Predis\Command\CommandInterface;
use Predis\Command\RawCommand;
use Predis\Connection\ConnectionException;
use Predis\Connection\FactoryInterface;
use Predis\Connection\NodeConnectionInterface;
use Predis\NotSupportedException;
use Predis\Response\ErrorInterface as ErrorResponseInterface;
use Predis\Response\ServerException;
use Predis\Response\Error as ErrorResponse;

/**
 * Abstraction for a Redis-backed cluster of nodes (Redis >= 3.0.0).
 *
 * This connection backend offers smart support for redis-cluster by handling
 * automatic slots map (re)generation upon -MOVED or -ASK responses returned by
 * Redis when redirecting a client to a different node.
 *
 * The cluster can be pre-initialized using only a subset of the actual nodes in
 * the cluster, Predis will do the rest by adjusting the slots map and creating
 * the missing underlying connection instances on the fly.
 *
 * It is possible to pre-associate connections to a slots range with the "slots"
 * parameter in the form "$first-$last". This can greatly reduce runtime node
 * guessing and redirections.
 *
 * It is also possible to ask for the full and updated slots map directly to one
 * of the nodes and optionally enable such a behaviour upon -MOVED redirections.
 * Asking for the cluster configuration to Redis is actually done by issuing a
 * CLUSTER SLOTS command to a random node in the pool.
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class RedisCluster implements ClusterInterface, \IteratorAggregate, \Countable
{
    private $useClusterSlots = true;
    private $pool = array();
    private $slots = array();
    private $slotmap;
    private $strategy;
    private $connections;
    private $retryLimit = 5;
    private $retryInterval = 10;

    /**
     * @param FactoryInterface  $connections Optional connection factory.
     * @param StrategyInterface $strategy    Optional cluster strategy.
     */
    public function __construct(
        FactoryInterface $connections,
        StrategyInterface $strategy = null
    ) {
        $this->connections = $connections;
        $this->strategy = $strategy ?: new RedisClusterStrategy();
        $this->slotmap = new SlotMap();
    }

    /**
     * Sets the maximum number of retries for commands upon server failure.
     *
     * -1 = unlimited retry attempts
     *  0 = no retry attempts (fails immediatly)
     *  n = fail only after n retry attempts
     *
     * @param int $retry Number of retry attempts.
     */
    public function setRetryLimit($retry)
    {
        $this->retryLimit = (int) $retry;
    }

    /**
     * Sets the initial retry interval (milliseconds).
     * 
     * @param int $retryInterval Milliseconds between retries.
     */
    public function setRetryInterval($retryInterval)
    {
        $this->retryInterval = (int) $retryInterval;
    }

    /**
     * Returns the retry interval (milliseconds).
     *
     * @return int Milliseconds between retries.
     */
    public function getRetryInterval()
    {
        return (int) $this->retryInterval;
    }

    /**
     * {@inheritdoc}
     */
    public function isConnected()
    {
        foreach ($this->pool as $connection) {
            if ($connection->isConnected()) {
                return true;
            }
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function connect()
    {
        if ($connection = $this->getRandomConnection()) {
            $connection->connect();
        }
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
     * {@inheritdoc}
     */
    public function add(NodeConnectionInterface $connection)
    {
        $this->pool[(string) $connection] = $connection;
        $this->slotmap->reset();
    }

    /**
     * {@inheritdoc}
     */
    public function remove(NodeConnectionInterface $connection)
    {
        if (false !== $id = array_search($connection, $this->pool, true)) {
            $this->slotmap->reset();
            $this->slots = array_diff($this->slots, array($connection));
            unset($this->pool[$id]);

            return true;
        }

        return false;
    }

    /**
     * Removes a connection instance by using its identifier.
     *
     * @param string $connectionID Connection identifier.
     *
     * @return bool True if the connection was in the pool.
     */
    public function removeById($connectionID)
    {
        if (isset($this->pool[$connectionID])) {
            $this->slotmap->reset();
            $this->slots = array_diff($this->slots, array($connectionID));
            unset($this->pool[$connectionID]);

            return true;
        }

        return false;
    }

    /**
     * Generates the current slots map by guessing the cluster configuration out
     * of the connection parameters of the connections in the pool.
     *
     * Generation is based on the same algorithm used by Redis to generate the
     * cluster, so it is most effective when all of the connections supplied on
     * initialization have the "slots" parameter properly set accordingly to the
     * current cluster configuration.
     */
    public function buildSlotMap()
    {
        $this->slotmap->reset();

        foreach ($this->pool as $connectionID => $connection) {
            $parameters = $connection->getParameters();

            if (!isset($parameters->slots)) {
                continue;
            }

            foreach (explode(',', $parameters->slots) as $slotRange) {
                $slots = explode('-', $slotRange, 2);

                if (!isset($slots[1])) {
                    $slots[1] = $slots[0];
                }

                $this->slotmap->setSlots($slots[0], $slots[1], $connectionID);
            }
        }
    }

    /**
     * Queries the specified node of the cluster to fetch the updated slots map.
     *
     * When the connection fails, this method tries to execute the same command
     * on a different connection picked at random from the pool of known nodes,
     * up until the retry limit is reached.
     *
     * @param NodeConnectionInterface $connection Connection to a node of the cluster.
     *
     * @return mixed
     */
    private function queryClusterNodeForSlotMap(NodeConnectionInterface $connection)
    {
        $retries = 0;
        $retryAfter = $this->retryInterval;
        $command = RawCommand::create('CLUSTER', 'SLOTS');

        RETRY_COMMAND: {
            try {
                $response = $connection->executeCommand($command);
            } catch (ConnectionException $exception) {
                $connection = $exception->getConnection();
                $connection->disconnect();

                $this->remove($connection);

                if ($retries === $this->retryLimit) {
                    throw $exception;
                }

                if (!$connection = $this->getRandomConnection()) {
                    throw new ClientException('No connections left in the pool for `CLUSTER SLOTS`');
                }

                usleep($retryAfter * 1000);
                $retryAfter = $retryAfter * 2;
                ++$retries;

                goto RETRY_COMMAND;
            }
        }

        return $response;
    }

    /**
     * Generates an updated slots map fetching the cluster configuration using
     * the CLUSTER SLOTS command against the specified node or a random one from
     * the pool.
     *
     * @param NodeConnectionInterface $connection Optional connection instance.
     */
    public function askSlotMap(NodeConnectionInterface $connection = null)
    {
        if (!$connection && !$connection = $this->getRandomConnection()) {
            return;
        }

        $this->slotmap->reset();

        $response = $this->queryClusterNodeForSlotMap($connection);

        foreach ($response as $slots) {
            // We only support master servers for now, so we ignore subsequent
            // elements in the $slots array identifying slaves.
            list($start, $end, $master) = $slots;

            if ($master[0] === '') {
                $this->slotmap->setSlots($start, $end, (string) $connection);
            } else {
                $this->slotmap->setSlots($start, $end, "{$master[0]}:{$master[1]}");
            }
        }
    }

    /**
     * Guesses the correct node associated to a given slot using a precalculated
     * slots map, falling back to the same logic used by Redis to initialize a
     * cluster (best-effort).
     *
     * @param int $slot Slot index.
     *
     * @return string Connection ID.
     */
    protected function guessNode($slot)
    {
        if (!$this->pool) {
            throw new ClientException('No connections available in the pool');
        }

        if ($this->slotmap->isEmpty()) {
            $this->buildSlotMap();
        }

        if ($node = $this->slotmap[$slot]) {
            return $node;
        }

        $count = count($this->pool);
        $index = min((int) ($slot / (int) (16384 / $count)), $count - 1);
        $nodes = array_keys($this->pool);

        return $nodes[$index];
    }

    /**
     * Creates a new connection instance from the given connection ID.
     *
     * @param string $connectionID Identifier for the connection.
     *
     * @return NodeConnectionInterface
     */
    protected function createConnection($connectionID)
    {
        $separator = strrpos($connectionID, ':');

        return $this->connections->create(array(
            'host' => substr($connectionID, 0, $separator),
            'port' => substr($connectionID, $separator + 1),
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function getConnectionByCommand(CommandInterface $command)
    {
        $slot = $this->strategy->getSlot($command);

        if (!isset($slot)) {
            throw new NotSupportedException(
                "Cannot use '{$command->getId()}' with redis-cluster."
            );
        }

        if (isset($this->slots[$slot])) {
            return $this->slots[$slot];
        } else {
            return $this->getConnectionBySlot($slot);
        }
    }

    /**
     * Returns the connection currently associated to a given slot.
     *
     * @param int $slot Slot index.
     *
     * @throws \OutOfBoundsException
     *
     * @return NodeConnectionInterface
     */
    public function getConnectionBySlot($slot)
    {
        if (!SlotMap::isValid($slot)) {
            throw new \OutOfBoundsException("Invalid slot [$slot].");
        }

        if (isset($this->slots[$slot])) {
            return $this->slots[$slot];
        }

        $connectionID = $this->guessNode($slot);

        if (!$connection = $this->getConnectionById($connectionID)) {
            $connection = $this->createConnection($connectionID);
            $this->pool[$connectionID] = $connection;
        }

        return $this->slots[$slot] = $connection;
    }

    /**
     * {@inheritdoc}
     */
    public function getConnectionById($connectionID)
    {
        if (isset($this->pool[$connectionID])) {
            return $this->pool[$connectionID];
        }
    }

    /**
     * Returns a random connection from the pool.
     *
     * @return NodeConnectionInterface|null
     */
    protected function getRandomConnection()
    {
        if ($this->pool) {
            return $this->pool[array_rand($this->pool)];
        }
    }

    /**
     * Permanently associates the connection instance to a new slot.
     * The connection is added to the connections pool if not yet included.
     *
     * @param NodeConnectionInterface $connection Connection instance.
     * @param int                     $slot       Target slot index.
     */
    protected function move(NodeConnectionInterface $connection, $slot)
    {
        $this->pool[(string) $connection] = $connection;
        $this->slots[(int) $slot] = $connection;
        $this->slotmap[(int) $slot] = $connection;
    }

    /**
     * Handles -ERR responses returned by Redis.
     *
     * @param CommandInterface       $command Command that generated the -ERR response.
     * @param ErrorResponseInterface $error   Redis error response object.
     *
     * @return mixed
     */
    protected function onErrorResponse(CommandInterface $command, ErrorResponseInterface $error)
    {
        $details = explode(' ', $error->getMessage(), 2);

        switch ($details[0]) {
            case 'MOVED':
                return $this->onMovedResponse($command, $details[1]);

            case 'ASK':
                return $this->onAskResponse($command, $details[1]);

            default:
                return $error;
        }
    }

    /**
     * Handles -MOVED responses by executing again the command against the node
     * indicated by the Redis response.
     *
     * @param CommandInterface $command Command that generated the -MOVED response.
     * @param string           $details Parameters of the -MOVED response.
     *
     * @return mixed
     */
    protected function onMovedResponse(CommandInterface $command, $details)
    {
        list($slot, $connectionID) = explode(' ', $details, 2);

        if (!$connection = $this->getConnectionById($connectionID)) {
            $connection = $this->createConnection($connectionID);
        }

        if ($this->useClusterSlots) {
            $this->askSlotMap($connection);
        }

        $this->move($connection, $slot);
        return $this->executeCommand($command);
    }

    /**
     * Handles -ASK responses by executing again the command against the node
     * indicated by the Redis response.
     *
     * @param CommandInterface $command Command that generated the -ASK response.
     * @param string           $details Parameters of the -ASK response.
     *
     * @return mixed
     */
    protected function onAskResponse(CommandInterface $command, $details)
    {
        list($slot, $connectionID) = explode(' ', $details, 2);

        if (!$connection = $this->getConnectionById($connectionID)) {
            $connection = $this->createConnection($connectionID);
        }

        $connection->executeCommand(RawCommand::create('ASKING'));
        return $connection->executeCommand($command);
    }

    /**
     * Ensures that a command is executed one more time on connection failure.
     *
     * The connection to the node that generated the error is evicted from the
     * pool before trying to fetch an updated slots map from another node. If
     * the new slots map points to an unreachable server the client gives up and
     * throws the exception as the nodes participating in the cluster may still
     * have to agree that something changed in the configuration of the cluster.
     *
     * @param CommandInterface $command Command instance.
     * @param string           $method  Actual method.
     *
     * @return mixed
     */
    private function retryCommandOnFailure(CommandInterface $command, $method)
    {
        $retries = 0;
        $retryAfter = $this->retryInterval;

        RETRY_COMMAND: {
            try {
                $response = $this->getConnectionByCommand($command)->$method($command);

                if ($response instanceof ErrorResponse) {
                    $message = $response->getMessage();

                    if (strpos($message, 'CLUSTERDOWN') !== false) {
                        throw new ServerException($message);
                    }
                }
            } catch (\Throwable $exception) {
                usleep($retryAfter * 1000);
                $retryAfter = $retryAfter * 2;

                if ($exception instanceof ConnectionException) {
                    $connection = $exception->getConnection();
                    
                    if ($connection) {
                        $connection->disconnect();
                        $this->remove($connection);
                    }
                }

                if ($retries === $this->retryLimit) {
                    throw $exception;
                } elseif ($this->useClusterSlots) {
                    $this->askSlotMap();
                }

                ++$retries;

                goto RETRY_COMMAND;
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
        $response = $this->retryCommandOnFailure($command, __FUNCTION__);

        if ($response instanceof ErrorResponseInterface) {
            return $this->onErrorResponse($command, $response);
        }

        return $response;
    }

    /**
     * {@inheritdoc}
     */
    #[\ReturnTypeWillChange]
    public function count()
    {
        return count($this->pool);
    }

    /**
     * {@inheritdoc}
     */
    #[\ReturnTypeWillChange]
    public function getIterator()
    {
        if ($this->slotmap->isEmpty()) {
            $this->useClusterSlots ? $this->askSlotMap() : $this->buildSlotMap();
        }

        $connections = array();

        foreach ($this->slotmap->getNodes() as $node) {
            if (!$connection = $this->getConnectionById($node)) {
                $this->add($connection = $this->createConnection($node));
            }

            $connections[] = $connection;
        }

        return new \ArrayIterator($connections);
    }

    /**
     * Returns the underlying slot map.
     *
     * @return SlotMap
     */
    public function getSlotMap()
    {
        return $this->slotmap;
    }

    /**
     * Returns the underlying command hash strategy used to hash commands by
     * using keys found in their arguments.
     *
     * @return StrategyInterface
     */
    public function getClusterStrategy()
    {
        return $this->strategy;
    }

    /**
     * Returns the underlying connection factory used to create new connection
     * instances to Redis nodes indicated by redis-cluster.
     *
     * @return FactoryInterface
     */
    public function getConnectionFactory()
    {
        return $this->connections;
    }

    /**
     * Enables automatic fetching of the current slots map from one of the nodes
     * using the CLUSTER SLOTS command. This option is enabled by default as
     * asking the current slots map to Redis upon -MOVED responses may reduce
     * overhead by eliminating the trial-and-error nature of the node guessing
     * procedure, mostly when targeting many keys that would end up in a lot of
     * redirections.
     *
     * The slots map can still be manually fetched using the askSlotMap()
     * method whether or not this option is enabled.
     *
     * @param bool $value Enable or disable the use of CLUSTER SLOTS.
     */
    public function useClusterSlots($value)
    {
        $this->useClusterSlots = (bool) $value;
    }
}
