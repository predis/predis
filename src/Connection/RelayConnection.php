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

namespace Predis\Connection;

use InvalidArgumentException;
use Predis\ClientException;
use Predis\Command\CommandInterface;
use Predis\NotSupportedException;
use Predis\Response\ErrorInterface as ErrorResponseInterface;
use Predis\Response\ServerException;
use Relay\Exception as RelayException;
use Relay\Relay;

/**
 * This class provides the implementation of a Predis connection that
 * uses Relay for network communication and in-memory caching.
 *
 * Using Relay allows for:
 * 1) significantly faster reads thanks to in-memory caching
 * 2) fast data serialization using igbinary
 * 3) fast data compression using lzf, lz4 or zstd
 *
 * Usage of igbinary serialization and zstd compresses reduces
 * network traffic and Redis memory usage by ~75%.
 *
 * For instructions on how to install the Relay extension, please consult
 * the repository of the project: https://relay.so/docs/installation
 *
 * The connection parameters supported by this class are:
 *
 *  - scheme: it can be either 'tcp', 'tls' or 'unix'.
 *  - host: hostname or IP address of the server.
 *  - port: TCP port of the server.
 *  - path: path of a UNIX domain socket when scheme is 'unix'.
 *  - timeout: timeout to perform the connection.
 *  - read_write_timeout: timeout of read / write operations.
 *  - cache: whether to use in-memory caching
 *  - serializer: data serializer
 *  - compression: data compression algorithm
 *
 * @see https://github.com/cachewerk/relay
 */
class RelayConnection extends AbstractConnection
{
    use RelayMethods;

    /**
     * The Relay instance.
     *
     * @var Relay
     */
    protected $client;

    /**
     * These commands must be called on the client, not using `Relay::rawCommand()`.
     *
     * @var string[]
     */
    public $atypicalCommands = [
        'AUTH',

        'TYPE',

        'MULTI',
        'EXEC',
        'DISCARD',

        'WATCH',
        'UNWATCH',

        'SUBSCRIBE',
        'UNSUBSCRIBE',
        'PSUBSCRIBE',
        'PUNSUBSCRIBE',
        'SSUBSCRIBE',
        'SUNSUBSCRIBE',
    ];

    /**
     * {@inheritdoc}
     */
    public function __construct(ParametersInterface $parameters, Relay $client)
    {
        $this->assertExtensions();

        $this->parameters = $this->assertParameters($parameters);
        $this->client = $client;
    }

    /**
     * {@inheritdoc}
     */
    public function isConnected()
    {
        return $this->client->isConnected();
    }

    /**
     * {@inheritdoc}
     */
    public function disconnect()
    {
        if ($this->client->isConnected()) {
            $this->client->close();
        }
    }

    /**
     * Checks if the Relay extension is loaded in PHP.
     */
    private function assertExtensions()
    {
        if (!extension_loaded('relay')) {
            throw new NotSupportedException(
                'The "relay" extension is required by this connection backend.'
            );
        }
    }

    /**
     * Creates a new instance of the client.
     *
     * @return Relay
     */
    private function createClient()
    {
        $client = new Relay();

        // throw when errors occur and return `null` for non-existent keys
        $client->setOption(Relay::OPT_PHPREDIS_COMPATIBILITY, false);

        // use reply literals
        $client->setOption(Relay::OPT_REPLY_LITERAL, true);

        // disable Relay's command/connection retry
        $client->setOption(Relay::OPT_MAX_RETRIES, 0);

        // whether to use in-memory caching
        $client->setOption(Relay::OPT_USE_CACHE, $this->parameters->cache ?? true);

        // set data serializer
        $client->setOption(Relay::OPT_SERIALIZER, constant(sprintf(
            '%s::SERIALIZER_%s',
            Relay::class,
            strtoupper($this->parameters->serializer ?? 'none')
        )));

        // set data compression algorithm
        $client->setOption(Relay::OPT_COMPRESSION, constant(sprintf(
            '%s::COMPRESSION_%s',
            Relay::class,
            strtoupper($this->parameters->compression ?? 'none')
        )));

        return $client;
    }

    /**
     * Returns the underlying client.
     *
     * @return Relay
     */
    public function getClient()
    {
        return $this->client;
    }

    /**
     * @param  ParametersInterface $parameters
     * @param                      $address
     * @param                      $flags
     * @return Relay
     */
    protected function connectWithConfiguration(ParametersInterface $parameters, $address, $flags)
    {
        $timeout = isset($parameters->timeout) ? (float) $parameters->timeout : 5.0;

        $retry_interval = 0;
        $read_timeout = 5.0;

        if (isset($parameters->read_write_timeout)) {
            $read_timeout = (float) $parameters->read_write_timeout;
            $read_timeout = $read_timeout > 0 ? $read_timeout : 0;
        }

        try {
            $this->client->connect(
                $parameters->path ?? $parameters->host,
                isset($parameters->path) ? 0 : $parameters->port,
                $timeout,
                null,
                $retry_interval,
                $read_timeout
            );
        } catch (RelayException $ex) {
            $this->onConnectionError($ex->getMessage(), $ex->getCode());
        }

        return $this->client;
    }

    /**
     * {@inheritdoc}
     */
    public function getIdentifier()
    {
        try {
            return $this->client->endpointId();
        } catch (RelayException $ex) {
            return parent::getIdentifier();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function executeCommand(CommandInterface $command)
    {
        if (!$this->client->isConnected()) {
            $this->getResource();
        }

        try {
            $name = $command->getId();

            // When using compression or a serializer, we'll need a dedicated
            // handler for `Predis\Command\RawCommand` calls, currently both
            // parameters are unsupported until a future Relay release
            return in_array($name, $this->atypicalCommands)
                ? $this->client->{$name}(...$command->getArguments())
                : $this->client->rawCommand($name, ...$command->getArguments());
        } catch (RelayException $ex) {
            $exception = $this->onCommandError($ex, $command);

            if ($exception instanceof ErrorResponseInterface) {
                return $exception;
            }

            throw $exception;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function onCommandError(RelayException $exception, CommandInterface $command)
    {
        $code = $exception->getCode();
        $message = $exception->getMessage();

        if (strpos($message, 'RELAY_ERR_IO') !== false) {
            return new ConnectionException($this, $message, $code, $exception);
        }

        if (strpos($message, 'RELAY_ERR_REDIS') !== false) {
            return new ServerException($message, $code, $exception);
        }

        if (strpos($message, 'RELAY_ERR_WRONGTYPE') !== false && strpos($message, "Got reply-type 'status'") !== false) {
            $message = 'Operation against a key holding the wrong kind of value';
        }

        return new ClientException($message, $code, $exception);
    }

    /**
     * Applies the configured serializer and compression to given value.
     *
     * @param  mixed  $value
     * @return string
     */
    public function pack($value)
    {
        return $this->client->_pack($value);
    }

    /**
     * Deserializes and decompresses to given value.
     *
     * @param  mixed  $value
     * @return string
     */
    public function unpack($value)
    {
        return $this->client->_unpack($value);
    }

    /**
     * {@inheritdoc}
     */
    public function writeRequest(CommandInterface $command)
    {
        throw new NotSupportedException('The "relay" extension does not support writing requests.');
    }

    /**
     * {@inheritdoc}
     */
    public function readResponse(CommandInterface $command)
    {
        throw new NotSupportedException('The "relay" extension does not support reading responses.');
    }

    /**
     * {@inheritdoc}
     */
    public function __destruct()
    {
        $this->disconnect();
    }

    /**
     * {@inheritdoc}
     */
    protected function createResource()
    {
        switch ($this->parameters->scheme) {
            case 'tcp':
            case 'redis':
                return $this->initializeTcpConnection($this->parameters);

            case 'unix':
                return $this->initializeUnixConnection($this->parameters);

            default:
                throw new InvalidArgumentException("Invalid scheme: '{$this->parameters->scheme}'.");
        }
    }

    /**
     * Initializes a TCP connection via client.
     *
     * @param ParametersInterface $parameters Initialization parameters for the connection.
     *
     * @return Relay
     */
    protected function initializeTcpConnection(ParametersInterface $parameters)
    {
        if (!filter_var($parameters->host, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            $address = "tcp://$parameters->host:$parameters->port";
        } else {
            $address = "tcp://[$parameters->host]:$parameters->port";
        }

        $flags = STREAM_CLIENT_CONNECT;

        if (isset($parameters->async_connect) && $parameters->async_connect) {
            $flags |= STREAM_CLIENT_ASYNC_CONNECT;
        }

        if (isset($parameters->persistent)) {
            if (false !== $persistent = filter_var($parameters->persistent, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE)) {
                $flags |= STREAM_CLIENT_PERSISTENT;

                if ($persistent === null) {
                    $address = "{$address}/{$parameters->persistent}";
                }
            }
        }

        return $this->connectWithConfiguration($parameters, $address, $flags);
    }

    /**
     * Initializes a UNIX connection via client.
     *
     * @param ParametersInterface $parameters Initialization parameters for the connection.
     *
     * @return Relay
     */
    protected function initializeUnixConnection(ParametersInterface $parameters)
    {
        if (!isset($parameters->path)) {
            throw new InvalidArgumentException('Missing UNIX domain socket path.');
        }

        $flags = STREAM_CLIENT_CONNECT;

        if (isset($parameters->persistent)) {
            if (false !== $persistent = filter_var($parameters->persistent, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE)) {
                $flags |= STREAM_CLIENT_PERSISTENT;

                if ($persistent === null) {
                    throw new InvalidArgumentException(
                        'Persistent connection IDs are not supported when using UNIX domain sockets.'
                    );
                }
            }
        }

        return $this->connectWithConfiguration($parameters, "unix://{$parameters->path}", $flags);
    }

    /**
     * {@inheritdoc}
     */
    public function connect()
    {
        if (parent::connect() && $this->initCommands) {
            foreach ($this->initCommands as $command) {
                $response = $this->executeCommand($command);

                if ($response instanceof ErrorResponseInterface && ($command->getId() === 'CLIENT')) {
                    // Do nothing on CLIENT SETINFO command failure
                } elseif ($response instanceof ErrorResponseInterface) {
                    $this->onConnectionError("`{$command->getId()}` failed: {$response->getMessage()}", 0);
                }
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function read()
    {
        throw new NotSupportedException('The "relay" extension does not support reading responses.');
    }

    /**
     * {@inheritdoc}
     */
    protected function assertParameters(ParametersInterface $parameters)
    {
        if (!in_array($parameters->scheme, ['tcp', 'tls', 'unix', 'redis', 'rediss'])) {
            throw new InvalidArgumentException("Invalid scheme: '{$parameters->scheme}'.");
        }

        if (!in_array($parameters->serializer, [null, 'php', 'igbinary', 'msgpack', 'json'])) {
            throw new InvalidArgumentException("Invalid serializer: '{$parameters->serializer}'.");
        }

        if (!in_array($parameters->compression, [null, 'lzf', 'lz4', 'zstd'])) {
            throw new InvalidArgumentException("Invalid compression algorithm: '{$parameters->compression}'.");
        }

        return $parameters;
    }

    /**
     * {@inheritDoc}
     */
    public function write(string $buffer): void
    {
        throw new NotSupportedException('The "relay" extension does not support writing operations.');
    }
}
