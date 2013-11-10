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

use InvalidArgumentException;
use Predis\Command\CommandInterface;
use Predis\Command\ScriptedCommand;
use Predis\Configuration\Options;
use Predis\Configuration\OptionsInterface;
use Predis\Connection\AggregatedConnectionInterface;
use Predis\Connection\ConnectionInterface;
use Predis\Connection\ConnectionFactoryInterface;
use Predis\Monitor\MonitorContext;
use Predis\Pipeline\PipelineContext;
use Predis\Profile\ServerProfile;
use Predis\PubSub\PubSubContext;
use Predis\Transaction\MultiExecContext;

/**
 * Main class that exposes the most high-level interface to interact with Redis.
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class Client implements ClientInterface
{
    const VERSION = '0.9.0-dev';

    protected $connection;
    protected $options;
    private $profile;

    /**
     * Initializes a new client with optional connection parameters and client options.
     *
     * @param mixed $parameters Connection parameters for one or multiple servers.
     * @param mixed $options Options that specify certain behaviours for the client.
     */
    public function __construct($parameters = null, $options = null)
    {
        $this->options    = $this->createOptions($options);
        $this->connection = $this->createConnection($parameters);
        $this->profile    = $this->options->profile;
    }

    /**
     * Creates a new instance of Predis\Configuration\Options from various
     * types of arguments or returns the passed object if it is an instance
     * of Predis\Configuration\OptionsInterface.
     *
     * @param mixed $options Client options.
     * @return OptionsInterface
     */
    protected function createOptions($options)
    {
        if (!isset($options)) {
            return new Options();
        }

        if (is_array($options)) {
            return new Options($options);
        }

        if ($options instanceof OptionsInterface) {
            return $options;
        }

        throw new InvalidArgumentException("Invalid type for client options");
    }

    /**
     * Initializes one or multiple connection (cluster) objects from various
     * types of arguments (string, array) or returns the passed object if it
     * implements Predis\Connection\ConnectionInterface.
     *
     * @param mixed $parameters Connection parameters or instance.
     * @return ConnectionInterface
     */
    protected function createConnection($parameters)
    {
        if ($parameters instanceof ConnectionInterface) {
            return $parameters;
        }

        if (is_array($parameters) && isset($parameters[0])) {
            $options = $this->options;
            $replication = isset($options->replication) && $options->replication;
            $connection = $options->{$replication ? 'replication' : 'cluster'};

            return $options->connections->createAggregated($connection, $parameters);
        }

        if (is_callable($parameters)) {
            $connection = call_user_func($parameters, $this->options);

            if (!$connection instanceof ConnectionInterface) {
                throw new \InvalidArgumentException(
                    'Callable parameters must return instances of Predis\Connection\ConnectionInterface'
                );
            }

            return $connection;
        }

        return $this->options->connections->create($parameters);
    }

    /**
     * {@inheritdoc}
     */
    public function getProfile()
    {
        return $this->profile;
    }

    /**
     * {@inheritdoc}
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * Returns a new instance of a client for the specified connection when the
     * client is connected to a cluster. The new instance will use the same
     * options of the original client.
     *
     * @return Client
     */
    public function getClientFor($connectionID)
    {
        if (!$connection = $this->getConnectionById($connectionID)) {
            throw new \InvalidArgumentException("Invalid connection ID: '$connectionID'");
        }

        return new static($connection, $this->options);
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
     * {@inheritdoc}
     */
    public function getConnection()
    {
        return $this->connection;
    }

    /**
     * Retrieves a single connection out of an aggregated connections instance.
     *
     * @param string $connectionId Index or alias of the connection.
     * @return Connection\SingleConnectionInterface
     */
    public function getConnectionById($connectionId)
    {
        if (!$this->connection instanceof AggregatedConnectionInterface) {
            throw new NotSupportedException('Retrieving connections by ID is supported only when using aggregated connections');
        }

        return $this->connection->getConnectionById($connectionId);
    }

    /**
     * Dynamically invokes a Redis command with the specified arguments.
     *
     * @param string $method The name of a Redis command.
     * @param array $arguments The arguments for the command.
     * @return mixed
     */
    public function __call($method, $arguments)
    {
        $command = $this->profile->createCommand($method, $arguments);
        $response = $this->connection->executeCommand($command);

        if ($response instanceof ResponseObjectInterface) {
            if ($response instanceof ResponseErrorInterface) {
                $response = $this->onResponseError($command, $response);
            }

            return $response;
        }

        return $command->parseResponse($response);
    }

    /**
     * {@inheritdoc}
     */
    public function createCommand($method, $arguments = array())
    {
        return $this->profile->createCommand($method, $arguments);
    }

    /**
     * {@inheritdoc}
     */
    public function executeCommand(CommandInterface $command)
    {
        $response = $this->connection->executeCommand($command);

        if ($response instanceof ResponseObjectInterface) {
            if ($response instanceof ResponseErrorInterface) {
                $response = $this->onResponseError($command, $response);
            }

            return $response;
        }

        return $command->parseResponse($response);
    }

    /**
     * Handles -ERR responses returned by Redis.
     *
     * @param CommandInterface $command The command that generated the error.
     * @param ResponseErrorInterface $response The error response instance.
     * @return mixed
     */
    protected function onResponseError(CommandInterface $command, ResponseErrorInterface $response)
    {
        if ($command instanceof ScriptedCommand && $response->getErrorType() === 'NOSCRIPT') {
            $eval = $this->createCommand('eval');
            $eval->setRawArguments($command->getEvalArguments());

            $response = $this->executeCommand($eval);

            if (!$response instanceof ResponseObjectInterface) {
                $response = $command->parseResponse($response);
            }

            return $response;
        }

        if ($this->options->exceptions) {
            throw new ServerException($response->getMessage());
        }

        return $response;
    }

    /**
     * Calls the specified initializer method on $this with 0, 1 or 2 arguments.
     *
     * @param string $initializer The initializer method.
     * @param array $argv Arguments for the initializer.
     * @return mixed
     */
    private function sharedContextFactory($initializer, $argv = null)
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
        return $this->sharedContextFactory('createPipeline', func_get_args());
    }

    /**
     * Pipeline context initializer.
     *
     * @param array $options Options for the context.
     * @param mixed $callable Optional callable object used to execute the context.
     * @return PipelineContext|array
     */
    protected function createPipeline(Array $options = null, $callable = null)
    {
        $executor = isset($options['executor']) ? $options['executor'] : null;

        if (is_callable($executor)) {
            $executor = call_user_func($executor, $this, $options);
        }

        $pipeline = new PipelineContext($this, $executor);

        if (isset($callable)) {
            return $pipeline->execute($callable);
        }

        return $pipeline;
    }

    /**
     * Creates a new transaction context and returns it, or returns the results of
     * a transaction executed inside the optionally provided callable object.
     *
     * @param mixed $arg,... Options for the context, a callable object, or both.
     * @return MultiExecContext|array
     */
    public function transaction(/* arguments */)
    {
        return $this->sharedContextFactory('createTransaction', func_get_args());
    }

    /**
     * Transaction context initializer.
     *
     * @param array $options Options for the context.
     * @param mixed $callable Optional callable object used to execute the context.
     * @return MultiExecContext|array
     */
    protected function createTransaction(Array $options = null, $callable = null)
    {
        $transaction = new MultiExecContext($this, $options ?: array());
        return isset($callable) ? $transaction->execute($callable) : $transaction;
    }

    /**
     * Creates a new Publish / Subscribe context and returns it, or executes it
     * inside the optionally provided callable object.
     *
     * @param mixed $arg,... Options for the context, a callable object, or both.
     * @return PubSubExecContext|array
     */
    public function pubSubLoop(/* arguments */)
    {
        return $this->sharedContextFactory('createPubSub', func_get_args());
    }

    /**
     * Publish / Subscribe context initializer.
     *
     * @param array $options Options for the context.
     * @param mixed $callable Optional callable object used to execute the context.
     * @return PubSubContext
     */
    protected function createPubSub(Array $options = null, $callable = null)
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
