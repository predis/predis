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
 * This class provides the implementation of a Predis connection that uses
 * Relay for network communication and wraps the C extension to parse
 * and serialize the Redis protocol.
 *
 * This class is intended to provide an optional low-overhead alternative for
 * processing responses from Redis compared to the standard pure-PHP classes.
 * Differences in speed when dealing with short inline responses are practically
 * nonexistent, the actual speed boost is for big multibulk responses when this
 * protocol processor can parse and return responses very fast.
 *
 * For instructions on how to install the Relay extension, please consult
 * the repository of the project: https://relay.so/docs/installation
 *
 * The connection parameters supported by this class are:
 *
 *  - scheme: it can be either 'redis', 'tcp' or 'unix'.
 *  - host: hostname or IP address of the server.
 *  - port: TCP port of the server.
 *  - path: path of a UNIX domain socket when scheme is 'unix'.
 *  - timeout: timeout to perform the connection.
 *  - read_write_timeout: timeout of read / write operations.
 *  - async_connect: performs the connection asynchronously.
 *  - persistent: the connection is left intact after a GC collection.
 *
 * @see https://github.com/cachewerk/relay
 */
class RelayConnection extends StreamConnection
{
    /**
     * The Relay instance.
     *
     * @var \Relay\Relay
     */
    private $reader;

    private $notBypassed = [
        'AUTH',
        'SELECT',
    ];

    /**
     * {@inheritdoc}
     */
    public function __construct(ParametersInterface $parameters)
    {
        $this->assertExtensions();

        $this->parameters = $this->assertParameters($parameters);
        $this->reader = $this->createReader();
    }

    /**
     * {@inheritdoc}
     */
    public function isConnected()
    {
        return $this->reader->isConnected();
    }

    /**
     * {@inheritdoc}
     */
    public function disconnect()
    {
        if ($this->isConnected()) {
            $this->reader->close();
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
        switch ($parameters->scheme) {
            case 'tcp':
            case 'redis':
            case 'unix':
                break;

            case 'tls':
            case 'rediss':
                break;
            default:
                throw new InvalidArgumentException("Invalid scheme: '$parameters->scheme'.");
        }

        return $parameters;
    }

    /**
     * Creates a new instance of the protocol reader resource.
     *
     * @return \Relay\Relay
     */
    private function createReader()
    {
        $reader = new Relay();
        $reader->setOption(Relay::OPT_PHPREDIS_COMPATIBILITY, false);

        return $reader;
    }

    /**
     * Returns the underlying protocol reader resource.
     *
     * @return resource
     */
    public function getReader()
    {
        return $this->reader;
    }

    /**
     * {@inheritdoc}
     */
    protected function createStreamSocket(ParametersInterface $parameters, $address, $flags)
    {
        $timeout = isset($parameters->timeout) ? (float) $parameters->timeout : 5.0;

        $read_timeout = 5.0;

        if (isset($parameters->read_write_timeout)) {
            $read_timeout = (float) $parameters->read_write_timeout;
            $read_timeout = $read_timeout > 0 ? $read_timeout : 0;
        }

        try {
            // TODO: `$flags` ???

            $this->reader->connect(
                $parameters->path ?? $parameters->host,
                isset($parameters->path) ? 0 : $parameters->port,
                $timeout,
                null,
                $retry_interval = 0,
                $read_timeout
            );
        } catch (RelayException $ex) {
            $this->onConnectionError($ex->getMessage(), $ex->getCode());
        }

        return $this->reader;
    }

    /**
     * {@inheritdoc}
     */
    public function executeCommand(CommandInterface $command)
    {
        if (!$this->reader->isConnected()) {
            $this->getResource();
        }

        try {
            $name = $command->getId();

            return in_array($name, $this->notBypassed)
                ? $this->reader->{$name}(...$command->getArguments())
                : $this->reader->rawCommand($name, ...$command->getArguments());
        } catch (RelayException $ex) {
            throw $this->onCommandError($ex);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function onCommandError(RelayException $exception)
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
     * {@inheritdoc}
     */
    public function writeRequest(CommandInterface $command)
    {
        throw new NotSupportedException(
            'The "relay" extension does not support writing requests.'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function readResponse(CommandInterface $command)
    {
        throw new NotSupportedException(
            'The "relay" extension does not support reading responses.'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function __destruct()
    {
        if (isset($this->parameters->persistent) && $this->parameters->persistent) {
            return;
        }

        $this->disconnect();
    }

    /**
     * {@inheritdoc}
     */
    public function __wakeup()
    {
        $this->assertExtensions();
        $this->reader = $this->createReader();
    }
}
