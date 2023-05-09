<?php

/*
 * This file is part of the Predis package.
 *
 * (c) 2009-2020 Daniele Alessandri
 * (c) 2021-2023 Till Krüss
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Connection;

use InvalidArgumentException;
use Predis\ClientException;
use Predis\Command\CommandInterface;
use Predis\NotSupportedException;
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
class RelayConnection extends StreamConnection
{
    use RelayMethods;

    /**
     * The Relay instance.
     *
     * @var \Relay\Relay
     */
    protected $client;

    /**
     * These commands must be called on the client, not using `Relay::rawCommand()`.
     *
     * @var string[]
     */
    public $atypicalCommands = [
        'AUTH',
        'SELECT',

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
    public function __construct(ParametersInterface $parameters)
    {
        $this->assertExtensions();

        $this->parameters = $this->assertParameters($parameters);
        $this->client = $this->createClient();
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
     * Creates a new instance of the client.
     *
     * @return \Relay\Relay
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
     * @return \Relay\Relay
     */
    public function getClient()
    {
        return $this->client;
    }

    /**
     * {@inheritdoc}
     */
    protected function getIdentifier()
    {
        return $this->client->endpointId();
    }

    /**
     * {@inheritdoc}
     */
    protected function createStreamSocket(ParametersInterface $parameters, $address, $flags)
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
            throw $this->onCommandError($ex, $command);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function onCommandError(RelayException $exception, CommandInterface $command)
    {
        $code = $exception->getCode();
        $message = $exception->getMessage();

        if (strpos($message, 'RELAY_ERR_IO')) {
            return new ConnectionException($this, $message, $code, $exception);
        }

        if (strpos($message, 'RELAY_ERR_REDIS')) {
            return new ServerException($message, $code, $exception);
        }

        if (strpos($message, 'RELAY_ERR_WRONGTYPE') && strpos($message, "Got reply-type 'status'")) {
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
    public function __wakeup()
    {
        $this->assertExtensions();
        $this->client = $this->createClient();
    }
}
