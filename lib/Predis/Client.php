<?php

/*
 * This file is part of the Predis package.
 *
 * (c) Daniele Alessandri <suppakilla@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis;

use Predis\Commands\ICommand;
use Predis\Options\IClientOptions;
use Predis\Network\IConnection;
use Predis\Network\IConnectionSingle;
use Predis\Profiles\IServerProfile;
use Predis\Options\ClientOptions;
use Predis\Profiles\ServerProfile;
use Predis\PubSub\PubSubContext;
use Predis\Pipeline\PipelineContext;
use Predis\Transaction\MultiExecContext;

/**
 * Main class that exposes the most high-level interface to interact with Redis.
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class Client
{
    const VERSION = '0.7.1-dev';

    private $options;
    private $profile;
    private $connection;
    private $connections;

    /**
     * Initializes a new client with optional connection parameters and client options.
     *
     * @param mixed $parameters Connection parameters for one or multiple servers.
     * @param mixed $options Options that specify certain behaviours for the client.
     */
    public function __construct($parameters = null, $options = null)
    {
        $options = $this->filterOptions($options);
        $this->options = $options;
        $this->profile = $options->profile;
        $this->connections = $options->connections;
        $this->connection = $this->initializeConnection($parameters);
    }

    /**
     * Creates an instance of Predis\Options\ClientOptions from various types of
     * arguments (string, array, Predis\Profiles\ServerProfile) or returns the
     * passed object if it is an instance of Predis\Options\ClientOptions.
     *
     * @param mixed $options Client options.
     * @return ClientOptions
     */
    protected function filterOptions($options)
    {
        if ($options === null) {
            return new ClientOptions();
        }
        if (is_array($options)) {
            return new ClientOptions($options);
        }
        if ($options instanceof IClientOptions) {
            return $options;
        }
        if ($options instanceof IServerProfile || is_string($options)) {
            return new ClientOptions(array('profile' => $options));
        }

        throw new \InvalidArgumentException("Invalid type for client options");
    }

    /**
     * Initializes one or multiple connection (cluster) objects from various
     * types of arguments (string, array) or returns the passed object if it
     * implements the Predis\Network\IConnection interface.
     *
     * @param mixed $parameters Connection parameters or instance.
     * @return IConnection
     */
    protected function initializeConnection($parameters)
    {
        if ($parameters instanceof IConnection) {
            return $parameters;
        }

        if (is_array($parameters) && isset($parameters[0])) {
            $replication = isset($this->options->replication) && $this->options->replication;

            $connection = $this->options->{$replication ? 'replication' : 'cluster'};
            $initializer = $replication ? 'createReplication' : 'createCluster';

            return $this->connections->$initializer($connection, $parameters, $this->profile);
        }

        return $this->connections->create($parameters, $this->profile);
    }

    /**
     * Returns the server profile used by the client.
     *
     * @return IServerProfile
     */
    public function getProfile()
    {
        return $this->profile;
    }

    /**
     * Returns the client options specified upon initialization.
     *
     * @return ClientOptions
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * Returns the connection factory object used by the client.
     *
     * @return IConnectionFactory
     */
    public function getConnectionFactory()
    {
        return $this->connections;
    }

    /**
     * Returns a new instance of a client for the specified connection when the
     * client is connected to a cluster. The new instance will use the same
     * options of the original client.
     *
     * @return Client
     */
    public function getClientFor($connectionAlias)
    {
        if (($connection = $this->getConnection($connectionAlias)) === null) {
            throw new \InvalidArgumentException("Invalid connection alias: '$connectionAlias'");
        }

        return new Client($connection, $this->options);
    }

    /**
     * Opens the connection to the server.
     */
    public function connect()
    {
        $this->connection->connect();
    }

    /**
     * Disconnects from the server.
     */
    public function disconnect()
    {
        $this->connection->disconnect();
    }

    /**
     * Disconnects from the server.
     *
     * This method is an alias of disconnect().
     */
    public function quit()
    {
        $this->disconnect();
    }

    /**
     * Checks if the underlying connection is connected to Redis.
     *
     * @return Boolean True means that the connection is open.
     *                 False means that the connection is closed.
     */
    public function isConnected()
    {
        return $this->connection->isConnected();
    }

    /**
     * Returns the underlying connection instance or, when connected to a cluster,
     * one of the connection instances identified by its alias.
     *
     * @param string $id The alias of a connection when connected to a cluster.
     * @return IConnection
     */
    public function getConnection($id = null)
    {
        if (isset($id)) {
            if (!Helpers::isAggregated($this->connection)) {
                $message = 'Retrieving connections by alias is supported only with aggregated connections (cluster or replication)';
                throw new NotSupportedException($message);
            }
            return $this->connection->getConnectionById($id);
        }

        return $this->connection;
    }

    /**
     * Dinamically invokes a Redis command with the specified arguments.
     *
     * @param string $method The name of a Redis command.
     * @param array $arguments The arguments for the command.
     * @return mixed
     */
    public function __call($method, $arguments)
    {
        $command = $this->profile->createCommand($method, $arguments);
        return $this->connection->executeCommand($command);
    }

    /**
     * Creates a new instance of the specified Redis command.
     *
     * @param string $method The name of a Redis command.
     * @param array $arguments The arguments for the command.
     * @return ICommand
     */
    public function createCommand($method, $arguments = array())
    {
        return $this->profile->createCommand($method, $arguments);
    }

    /**
     * Executes the specified Redis command.
     *
     * @param ICommand $command A Redis command.
     * @return mixed
     */
    public function executeCommand(ICommand $command)
    {
        return $this->connection->executeCommand($command);
    }

    /**
     * Executes the specified Redis command on all the nodes of a cluster.
     *
     * @param ICommand $command A Redis command.
     * @return array
     */
    public function executeCommandOnShards(ICommand $command)
    {
        if (Helpers::isCluster($this->connection)) {
            $replies = array();

            foreach ($this->connection as $connection) {
                $replies[] = $connection->executeCommand($command);
            }

            return $replies;
        }

        return array($this->connection->executeCommand($command));
    }

    /**
     * Calls the specified initializer method on $this with 0, 1 or 2 arguments.
     *
     * TODO: Invert $argv and $initializer.
     *
     * @param array $argv Arguments for the initializer.
     * @param string $initializer The initializer method.
     * @return mixed
     */
    private function sharedInitializer($argv, $initializer)
    {
        switch (count($argv)) {
            case 0:
                return $this->$initializer();

            case 1:
                list($arg0) = $argv;
                return is_array($arg0) ? $this->$initializer($arg0) : $this->$initializer(null, $arg0);

            case 2:
                list($arg0, $arg1) = $argv;
                return $this->$initializer($arg0, $arg1);

            default:
                return $this->$initializer($this, $argv);
        }
    }

    /**
     * Creates a new pipeline context and returns it, or returns the results of
     * a pipeline executed inside the optionally provided callable object.
     *
     * @param mixed $arg,... Options for the context, a callable object, or both.
     * @return PipelineContext|array
     */
    public function pipeline(/* arguments */)
    {
        return $this->sharedInitializer(func_get_args(), 'initPipeline');
    }

    /**
     * Pipeline context initializer.
     *
     * @param array $options Options for the context.
     * @param mixed $callable Optional callable object used to execute the context.
     * @return PipelineContext|array
     */
    protected function initPipeline(Array $options = null, $callable = null)
    {
        $pipeline = new PipelineContext($this, $options);
        return $this->pipelineExecute($pipeline, $callable);
    }

    /**
     * Executes a pipeline context when a callable object is passed.
     *
     * @param array $options Options of the context initialization.
     * @param mixed $callable Optional callable object used to execute the context.
     * @return PipelineContext|array
     */
    private function pipelineExecute(PipelineContext $pipeline, $callable)
    {
        return isset($callable) ? $pipeline->execute($callable) : $pipeline;
    }

    /**
     * Creates a new transaction context and returns it, or returns the results of
     * a transaction executed inside the optionally provided callable object.
     *
     * @param mixed $arg,... Options for the context, a callable object, or both.
     * @return MultiExecContext|array
     */
    public function multiExec(/* arguments */)
    {
        return $this->sharedInitializer(func_get_args(), 'initMultiExec');
    }

    /**
     * Transaction context initializer.
     *
     * @param array $options Options for the context.
     * @param mixed $callable Optional callable object used to execute the context.
     * @return MultiExecContext|array
     */
    protected function initMultiExec(Array $options = null, $callable = null)
    {
        $transaction = new MultiExecContext($this, $options ?: array());
        return isset($callable) ? $transaction->execute($callable) : $transaction;
    }

    /**
     * Creates a new Publish / Subscribe context and returns it, or executes it
     * inside the optionally provided callable object.
     *
     * @param mixed $arg,... Options for the context, a callable object, or both.
     * @return MultiExecContext|array
     */
    public function pubSub(/* arguments */)
    {
        return $this->sharedInitializer(func_get_args(), 'initPubSub');
    }

    /**
     * Publish / Subscribe context initializer.
     *
     * @param array $options Options for the context.
     * @param mixed $callable Optional callable object used to execute the context.
     * @return PubSubContext
     */
    protected function initPubSub(Array $options = null, $callable = null)
    {
        $pubsub = new PubSubContext($this, $options);

        if (!isset($callable)) {
            return $pubsub;
        }

        foreach ($pubsub as $message) {
            if (call_user_func($callable, $pubsub, $message) === false) {
                $pubsub->closeContext();
            }
        }
    }

    /**
     * Returns a new monitor context.
     *
     * @return MonitorContext
     */
    public function monitor()
    {
        return new MonitorContext($this);
    }
}
