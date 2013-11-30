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
use UnexpectedValueException;
use Predis\Command\CommandInterface;
use Predis\Command\ScriptCommand;
use Predis\Configuration;
use Predis\Connection\AggregatedConnectionInterface;
use Predis\Connection\ConnectionInterface;
use Predis\Connection\ConnectionParametersInterface;
use Predis\Monitor;
use Predis\Pipeline;
use Predis\PubSub;
use Predis\Response;
use Predis\Transaction;

/**
 * Client class used for connecting and executing commands on Redis.
 *
 * This is the main high-level abstraction of Predis upon which various other
 * abstractions are built. Internally it aggregates various other classes each
 * one with its own responsibility and scope.
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
     * @param mixed $parameters Connection parameters for one or more servers.
     * @param mixed $options Options to configure some behaviours of the client.
     */
    public function __construct($parameters = null, $options = null)
    {
        $this->options    = $this->createOptions($options ?: array());
        $this->connection = $this->createConnection($parameters ?: array());
        $this->profile    = $this->options->profile;
    }

    /**
     * Creates a new instance of Predis\Configuration\Options from different
     * types of arguments or simply returns the passed argument if it is an
     * instance of Predis\Configuration\OptionsInterface.
     *
     * @param mixed $options Client options.
     * @return OptionsInterface
     */
    protected function createOptions($options)
    {
        if (is_array($options)) {
            return new Configuration\Options($options);
        }

        if ($options instanceof Configuration\OptionsInterface) {
            return $options;
        }

        throw new InvalidArgumentException("Invalid type for client options");
    }

    /**
     * Creates single or aggregate connections from different types of arguments
     * (string, array) or returns the passed argument if it is an instance of a
     * class implementing Predis\Connection\ConnectionInterface.
     *
     * Accepted types for connection parameters are:
     *
     *  - Instance of Predis\Connection\ConnectionInterface.
     *  - Instance of Predis\Connection\ConnectionParametersInterface.
     *  - Array
     *  - String
     *  - Callable
     *
     * @param mixed $parameters Connection parameters or connection instance.
     * @return ConnectionInterface
     */
    protected function createConnection($parameters)
    {
        if ($parameters instanceof ConnectionInterface) {
            return $parameters;
        }

        if ($parameters instanceof ConnectionParametersInterface || is_string($parameters)) {
            return $this->options->connections->create($parameters);
        }

        if (is_array($parameters)) {
            if (!isset($parameters[0])) {
                return $this->options->connections->create($parameters);
            }

            $options = $this->options;

            if ($options->defined('aggregate')) {
                $initializer = $this->getConnectionInitializerWrapper($options->aggregate);
                $connection = $initializer($parameters, $options);
            } else {
                if ($options->defined('replication') && $replication = $options->replication) {
                    $connection = $replication;
                } else {
                    $connection = $options->cluster;
                }

                $options->connections->aggregate($connection, $parameters);
            }

            return $connection;
        }

        if (is_callable($parameters)) {
            $initializer = $this->getConnectionInitializerWrapper($parameters);
            $connection = $initializer($this->options);

            return $connection;
        }

        throw new InvalidArgumentException('Invalid type for connection parameters');
    }

    /**
     * Wraps a callable to make sure that its returned value represents a valid
     * connection type.
     *
     * @param mixed $callable
     * @return mixed
     */
    protected function getConnectionInitializerWrapper($callable)
    {
        return function () use ($callable) {
            $connection = call_user_func_array($callable, func_get_args());

            if (!$connection instanceof ConnectionInterface) {
                throw new UnexpectedValueException('The callable connection initializer returned an invalid type');
            }

            return $connection;
        };
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
     * Creates a new client instance for the specified connection ID or alias,
     * only when working with an aggregate connection (cluster, replication).
     * The new client instances uses the same options of the original one.
     *
     * @return Client
     */
    public function getClientFor($connectionID)
    {
        if (!$connection = $this->getConnectionById($connectionID)) {
            throw new InvalidArgumentException("Invalid connection ID: $connectionID");
        }

        return new static($connection, $this->options);
    }

    /**
     * Opens the underlying connection and connects to the server.
     */
    public function connect()
    {
        $this->connection->connect();
    }

    /**
     * Closes the underlying connection and disconnects from the server.
     */
    public function disconnect()
    {
        $this->connection->disconnect();
    }

    /**
     * Closes the underlying connection and disconnects from the server.
     *
     * This is the same as `Client::disconnect()` as it does not actually send
     * the `QUIT` command to Redis, but simply closes the connection.
     */
    public function quit()
    {
        $this->disconnect();
    }

    /**
     * Returns the current state of the underlying connection.
     *
     * @return bool
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
     * Retrieves the specified connection from the aggregate connection when the
     * client is in cluster or replication mode.
     *
     * @param string $connectionID Index or alias of the single connection.
     * @return Connection\SingleConnectionInterface
     */
    public function getConnectionById($connectionID)
    {
        if (!$this->connection instanceof AggregatedConnectionInterface) {
            throw new NotSupportedException(
                'Retrieving connections by ID is supported only when using aggregated connections'
            );
        }

        return $this->connection->getConnectionById($connectionID);
    }

    /**
     * Creates a Redis command with the specified arguments and sends a request
     * to the server.
     *
     * @param string $commandID Command ID.
     * @param array $arguments Arguments for the command.
     * @return mixed
     */
    public function __call($commandID, $arguments)
    {
        $command  = $this->createCommand($commandID, $arguments);
        $response = $this->executeCommand($command);

        return $response;
    }

    /**
     * {@inheritdoc}
     */
    public function createCommand($commandID, $arguments = array())
    {
        return $this->profile->createCommand($commandID, $arguments);
    }

    /**
     * {@inheritdoc}
     */
    public function executeCommand(CommandInterface $command)
    {
        $response = $this->connection->executeCommand($command);

        if ($response instanceof Response\ObjectInterface) {
            if ($response instanceof Response\ErrorInterface) {
                $response = $this->onResponseError($command, $response);
            }

            return $response;
        }

        return $command->parseResponse($response);
    }

    /**
     * Handles -ERR responses returned by Redis.
     *
     * @param CommandInterface $command Redis command that generated the error.
     * @param Response\ErrorInterface $response Instance of the error response.
     * @return mixed
     */
    protected function onResponseError(CommandInterface $command, Response\ErrorInterface $response)
    {
        if ($command instanceof ScriptCommand && $response->getErrorType() === 'NOSCRIPT') {
            $eval = $this->createCommand('eval');
            $eval->setRawArguments($command->getEvalArguments());

            $response = $this->executeCommand($eval);

            if (!$response instanceof Response\ObjectInterface) {
                $response = $command->parseResponse($response);
            }

            return $response;
        }

        if ($this->options->exceptions) {
            throw new Response\ServerException($response->getMessage());
        }

        return $response;
    }

    /**
     * Executes the specified initializer method on `$this` by adjusting the
     * actual invokation depending on the arity (0, 1 or 2 arguments). This is
     * simply an utility method to create Redis contexts instances since they
     * follow a common initialization path.
     *
     * @param string $initializer Method name.
     * @param array $argv Arguments for the method.
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
     * @param mixed $arg,... Options for the context, or a callable, or both.
     * @return Pipeline\Pipeline|array
     */
    public function pipeline(/* arguments */)
    {
        return $this->sharedContextFactory('createPipeline', func_get_args());
    }

    /**
     * Actual pipeline context initializer method.
     *
     * @param array $options Options for the context.
     * @param mixed $callable Optional callable used to execute the context.
     * @return Pipeline\Pipeline|array
     */
    protected function createPipeline(array $options = null, $callable = null)
    {
        if (isset($options['atomic']) && $options['atomic']) {
            $class = 'Predis\Pipeline\Atomic';
        } else if (isset($options['fire-and-forget']) && $options['fire-and-forget']) {
            $class = 'Predis\Pipeline\FireAndForget';
        } else {
            $class = 'Predis\Pipeline\Pipeline';
        }

        $pipeline = new $class($this);

        if (isset($callable)) {
            return $pipeline->execute($callable);
        }

        return $pipeline;
    }

    /**
     * Creates a new transaction context and returns it, or returns the results
     * of a transaction executed inside the optionally provided callable object.
     *
     * @param mixed $arg,... Options for the context, or a callable, or both.
     * @return Transaction\MultiExec|array
     */
    public function transaction(/* arguments */)
    {
        return $this->sharedContextFactory('createTransaction', func_get_args());
    }

    /**
     * Actual transaction context initializer method.
     *
     * @param array $options Options for the context.
     * @param mixed $callable Optional callable used to execute the context.
     * @return Transaction\MultiExec|array
     */
    protected function createTransaction(array $options = null, $callable = null)
    {
        $transaction = new Transaction\MultiExec($this, $options);

        if (isset($callable)) {
            return $transaction->execute($callable);
        }

        return $transaction;
    }

    /**
     * Creates a new publis/subscribe context and returns it, or starts its loop
     * inside the optionally provided callable object.
     *
     * @param mixed $arg,... Options for the context, or a callable, or both.
     * @return PubSub\Consumer|NULL
     */
    public function pubSubLoop(/* arguments */)
    {
        return $this->sharedContextFactory('createPubSub', func_get_args());
    }

    /**
     * Actual publish/subscribe context initializer method.
     *
     * @param array $options Options for the context.
     * @param mixed $callable Optional callable used to execute the context.
     * @return PubSub\Consumer|NULL
     */
    protected function createPubSub(array $options = null, $callable = null)
    {
        $pubsub = new PubSub\Consumer($this, $options);

        if (!isset($callable)) {
            return $pubsub;
        }

        foreach ($pubsub as $message) {
            if (call_user_func($callable, $pubsub, $message) === false) {
                $pubsub->stop();
            }
        }
    }

    /**
     * Creates a new monitor consumer and returns it.
     *
     * @return Monitor\Consumer
     */
    public function monitor()
    {
        return new Monitor\Consumer($this);
    }
}
