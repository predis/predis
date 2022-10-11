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

use Predis\Command\CommandInterface;
use Predis\Command\RawCommand;
use Predis\Command\ScriptCommand;
use Predis\Configuration\Options;
use Predis\Configuration\OptionsInterface;
use Predis\Connection\ConnectionInterface;
use Predis\Connection\Parameters;
use Predis\Connection\ParametersInterface;
use Predis\Monitor\Consumer as MonitorConsumer;
use Predis\Pipeline\Pipeline;
use Predis\PubSub\Consumer as PubSubConsumer;
use Predis\Response\ErrorInterface as ErrorResponseInterface;
use Predis\Response\ResponseInterface;
use Predis\Response\ServerException;
use Predis\Transaction\MultiExec as MultiExecTransaction;

/**
 * Client class used for connecting and executing commands on Redis.
 *
 * This is the main high-level abstraction of Predis upon which various other
 * abstractions are built. Internally it aggregates various other classes each
 * one with its own responsibility and scope.
 *
 * {@inheritdoc}
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class Client implements ClientInterface, \IteratorAggregate
{
    const VERSION = '2.0.3';

    /** @var OptionsInterface */
    private $options;

    /** @var ConnectionInterface */
    private $connection;

    /** @var Command\FactoryInterface */
    private $commands;

    /**
     * @param mixed $parameters Connection parameters for one or more servers.
     * @param mixed $options    Options to configure some behaviours of the client.
     */
    public function __construct($parameters = null, $options = null)
    {
        $this->options = static::createOptions($options ?? new Options);
        $this->connection = static::createConnection($this->options, $parameters ?? new Parameters);
        $this->commands = $this->options->commands;
    }

    /**
     * Creates a new set of client options for the client.
     *
     * @param array|OptionsInterface $options Set of client options
     *
     * @throws \InvalidArgumentException
     *
     * @return OptionsInterface
     */
    protected static function createOptions($options)
    {
        if (is_array($options)) {
            return new Options($options);
        } elseif ($options instanceof OptionsInterface) {
            return $options;
        } else {
            throw new \InvalidArgumentException('Invalid type for client options');
        }
    }

    /**
     * Creates single or aggregate connections from supplied arguments.
     *
     * This method accepts the following types to create a connection instance:
     *
     *  - Array (dictionary: single connection, indexed: aggregate connections)
     *  - String (URI for a single connection)
     *  - Callable (connection initializer callback)
     *  - Instance of Predis\Connection\ParametersInterface (used as-is)
     *  - Instance of Predis\Connection\ConnectionInterface (returned as-is)
     *
     * When a callable is passed, it receives the original set of client options
     * and must return an instance of Predis\Connection\ConnectionInterface.
     *
     * Connections are created using the connection factory (in case of single
     * connections) or a specialized aggregate connection initializer (in case
     * of cluster and replication) retrieved from the supplied client options.
     *
     * @param OptionsInterface $options    Client options container
     * @param mixed            $parameters Connection parameters
     *
     * @throws \InvalidArgumentException
     *
     * @return ConnectionInterface
     */
    protected static function createConnection(OptionsInterface $options, $parameters)
    {
        if ($parameters instanceof ConnectionInterface) {
            return $parameters;
        }

        if ($parameters instanceof ParametersInterface || is_string($parameters)) {
            return $options->connections->create($parameters);
        }

        if (is_array($parameters)) {
            if (!isset($parameters[0])) {
                return $options->connections->create($parameters);
            } elseif ($options->defined('cluster') && $initializer = $options->cluster) {
                return $initializer($parameters, true);
            } elseif ($options->defined('replication') && $initializer = $options->replication) {
                return $initializer($parameters, true);
            } elseif ($options->defined('aggregate') && $initializer = $options->aggregate) {
                return $initializer($parameters, false);
            } else {
                throw new \InvalidArgumentException(
                    'Array of connection parameters requires `cluster`, `replication` or `aggregate` client option'
                );
            }
        }

        if (is_callable($parameters)) {
            $connection = call_user_func($parameters, $options);

            if (!$connection instanceof ConnectionInterface) {
                throw new \InvalidArgumentException('Callable parameters must return a valid connection');
            }

            return $connection;
        }

        throw new \InvalidArgumentException('Invalid type for connection parameters');
    }

    /**
     * {@inheritdoc}
     */
    public function getCommandFactory()
    {
        return $this->commands;
    }

    /**
     * {@inheritdoc}
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * Creates a new client using a specific underlying connection.
     *
     * This method allows to create a new client instance by picking a specific
     * connection out of an aggregate one, with the same options of the original
     * client instance.
     *
     * The specified selector defines which logic to use to look for a suitable
     * connection by the specified value. Supported selectors are:
     *
     *   - `id`
     *   - `key`
     *   - `slot`
     *   - `command`
     *   - `alias`
     *   - `role`
     *
     * Internally the client relies on duck-typing and follows this convention:
     *
     *   $selector string => getConnectionBy$selector($value) method
     *
     * This means that support for specific selectors may vary depending on the
     * actual logic implemented by connection classes and there is no interface
     * binding a connection class to implement any of these.
     *
     * @param string $selector Type of selector.
     * @param mixed  $value    Value to be used by the selector.
     *
     * @return ClientInterface
     */
    public function getClientBy($selector, $value)
    {
        $selector = strtolower($selector);

        if (!in_array($selector, array('id', 'key', 'slot', 'role', 'alias', 'command'))) {
            throw new \InvalidArgumentException("Invalid selector type: `$selector`");
        }

        if (!method_exists($this->connection, $method = "getConnectionBy$selector")) {
            $class = get_class($this->connection);
            throw new \InvalidArgumentException("Selecting connection by $selector is not supported by $class");
        }

        if (!$connection = $this->connection->$method($value)) {
            throw new \InvalidArgumentException("Cannot find a connection by $selector matching `$value`");
        }

        return new static($connection, $this->getOptions());
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
     * Executes a command without filtering its arguments, parsing the response,
     * applying any prefix to keys or throwing exceptions on Redis errors even
     * regardless of client options.
     *
     * It is possible to identify Redis error responses from normal responses
     * using the second optional argument which is populated by reference.
     *
     * @param array $arguments Command arguments as defined by the command signature.
     * @param bool  $error     Set to TRUE when Redis returned an error response.
     *
     * @return mixed
     */
    public function executeRaw(array $arguments, &$error = null)
    {
        $error = false;
        $commandID = array_shift($arguments);

        $response = $this->connection->executeCommand(
            new RawCommand($commandID, $arguments)
        );

        if ($response instanceof ResponseInterface) {
            if ($response instanceof ErrorResponseInterface) {
                $error = true;
            }

            return (string) $response;
        }

        return $response;
    }

    /**
     * {@inheritdoc}
     */
    public function __call($commandID, $arguments)
    {
        return $this->executeCommand(
            $this->createCommand($commandID, $arguments)
        );
    }

    /**
     * {@inheritdoc}
     */
    public function createCommand($commandID, $arguments = array())
    {
        return $this->commands->create($commandID, $arguments);
    }

    /**
     * {@inheritdoc}
     */
    public function executeCommand(CommandInterface $command)
    {
        $response = $this->connection->executeCommand($command);

        if ($response instanceof ResponseInterface) {
            if ($response instanceof ErrorResponseInterface) {
                $response = $this->onErrorResponse($command, $response);
            }

            return $response;
        }

        return $command->parseResponse($response);
    }

    /**
     * Handles -ERR responses returned by Redis.
     *
     * @param CommandInterface       $command  Redis command that generated the error.
     * @param ErrorResponseInterface $response Instance of the error response.
     *
     * @throws ServerException
     *
     * @return mixed
     */
    protected function onErrorResponse(CommandInterface $command, ErrorResponseInterface $response)
    {
        if ($command instanceof ScriptCommand && $response->getErrorType() === 'NOSCRIPT') {
            $response = $this->executeCommand($command->getEvalCommand());

            if (!$response instanceof ResponseInterface) {
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
     * Executes the specified initializer method on `$this` by adjusting the
     * actual invokation depending on the arity (0, 1 or 2 arguments). This is
     * simply an utility method to create Redis contexts instances since they
     * follow a common initialization path.
     *
     * @param string $initializer Method name.
     * @param array  $argv        Arguments for the method.
     *
     * @return mixed
     */
    private function sharedContextFactory($initializer, $argv = null)
    {
        switch (count($argv)) {
            case 0:
                return $this->$initializer();

            case 1:
                return is_array($argv[0])
                    ? $this->$initializer($argv[0])
                    : $this->$initializer(null, $argv[0]);

            case 2:
                list($arg0, $arg1) = $argv;

                return $this->$initializer($arg0, $arg1);

        // @codeCoverageIgnoreStart
            default:
                return $this->$initializer($this, $argv);
        }
        // @codeCoverageIgnoreEnd
    }

    /**
     * Creates a new pipeline context and returns it, or returns the results of
     * a pipeline executed inside the optionally provided callable object.
     *
     * @param mixed ... Array of options, a callable for execution, or both.
     *
     * @return Pipeline|array
     */
    public function pipeline(/* arguments */)
    {
        return $this->sharedContextFactory('createPipeline', func_get_args());
    }

    /**
     * Actual pipeline context initializer method.
     *
     * @param array $options  Options for the context.
     * @param mixed $callable Optional callable used to execute the context.
     *
     * @return Pipeline|array
     */
    protected function createPipeline(array $options = null, $callable = null)
    {
        if (isset($options['atomic']) && $options['atomic']) {
            $class = 'Predis\Pipeline\Atomic';
        } elseif (isset($options['fire-and-forget']) && $options['fire-and-forget']) {
            $class = 'Predis\Pipeline\FireAndForget';
        } else {
            $class = 'Predis\Pipeline\Pipeline';
        }

        /*
         * @var ClientContextInterface
         */
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
     * @param mixed ... Array of options, a callable for execution, or both.
     *
     * @return MultiExecTransaction|array
     */
    public function transaction(/* arguments */)
    {
        return $this->sharedContextFactory('createTransaction', func_get_args());
    }

    /**
     * Actual transaction context initializer method.
     *
     * @param array $options  Options for the context.
     * @param mixed $callable Optional callable used to execute the context.
     *
     * @return MultiExecTransaction|array
     */
    protected function createTransaction(array $options = null, $callable = null)
    {
        $transaction = new MultiExecTransaction($this, $options);

        if (isset($callable)) {
            return $transaction->execute($callable);
        }

        return $transaction;
    }

    /**
     * Creates a new publish/subscribe context and returns it, or starts its loop
     * inside the optionally provided callable object.
     *
     * @param mixed ... Array of options, a callable for execution, or both.
     *
     * @return PubSubConsumer|null
     */
    public function pubSubLoop(/* arguments */)
    {
        return $this->sharedContextFactory('createPubSub', func_get_args());
    }

    /**
     * Actual publish/subscribe context initializer method.
     *
     * @param array $options  Options for the context.
     * @param mixed $callable Optional callable used to execute the context.
     *
     * @return PubSubConsumer|null
     */
    protected function createPubSub(array $options = null, $callable = null)
    {
        $pubsub = new PubSubConsumer($this, $options);

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
     * @return MonitorConsumer
     */
    public function monitor()
    {
        return new MonitorConsumer($this);
    }

    /**
     * {@inheritdoc}
     */
    #[\ReturnTypeWillChange]
    public function getIterator()
    {
        $clients = array();
        $connection = $this->getConnection();

        if (!$connection instanceof \Traversable) {
            return new \ArrayIterator(array(
                (string) $connection => new static($connection, $this->getOptions())
            ));
        }

        foreach ($connection as $node) {
            $clients[(string) $node] = new static($node, $this->getOptions());
        }

        return new \ArrayIterator($clients);
    }
}
