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

use Closure;
use InvalidArgumentException;
use Relay\Relay;
use Relay\Exception as RelayException;
use Predis\NotSupportedException;
use Predis\Command\CommandInterface;
use Predis\Response\Error as ErrorResponse;
use Predis\Response\Status as StatusResponse;

/**
 * This class provides the implementation of a Predis connection that uses PHP's
 * streams for network communication and wraps the phpiredis C extension (PHP
 * bindings for hiredis) to parse and serialize the Redis protocol.
 *
 * This class is intended to provide an optional low-overhead alternative for
 * processing responses from Redis compared to the standard pure-PHP classes.
 * Differences in speed when dealing with short inline responses are practically
 * nonexistent, the actual speed boost is for big multibulk responses when this
 * protocol processor can parse and return responses very fast.
 *
 * For instructions on how to build and install the phpiredis extension, please
 * consult the repository of the project.
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
class RelayStreamConnection extends StreamConnection
{
    /**
     * The Relay instance.
     *
     * @var \Relay\Relay
     */
    private $reader;

    /**
     * {@inheritdoc}
     */
    public function __construct(ParametersInterface $parameters)
    {
        $this->assertExtensions();

        parent::__construct($parameters);

        $this->reader = $this->createReader();
    }

    /**
     * {@inheritdoc}
     */
    public function disconnect()
    {
        $this->reader->close();
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
     * {@inheritdoc}
     */
    protected function createStreamSocket(ParametersInterface $parameters, $address, $flags)
    {
        // TODO: rename function?!
        // TODO: client invalidations

        $timeout = (isset($parameters->timeout) ? (float) $parameters->timeout : 5.0);

        $read_timeout = 5.0;

        if (isset($parameters->read_write_timeout)) {
            $read_timeout = (float) $parameters->read_write_timeout;
            $read_timeout = $read_timeout > 0 ? $read_timeout : -1;
        }

        $url = parse_url($address);

        try {
            // TODO: support TLS, Scheme, Socket
            // TODO: `$flags` ???
            // TODO: compression

            $this->reader->connect(
                $url['host'],
                $url['port'],
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
     * Creates a new instance of the protocol reader resource.
     *
     * @return \Relay\Relay
     */
    private function createReader()
    {
        $reader = new Relay;
        $reader->setOption(Relay::OPT_PHPREDIS_COMPATIBILITY, false);

        // TODO: throw errors, without switching from `null` to `false` responses

        return $reader;
    }

    /**
     * Returns the underlying protocol reader resource.
     *
     * @return resource
     */
    protected function getReader()
    {
        return $this->reader;
    }

    // /**
    //  * Returns the handler used by the protocol reader for inline responses.
    //  *
    //  * @return Closure
    //  */
    // protected function getStatusHandler()
    // {
    //     static $statusHandler;

    //     if (!$statusHandler) {
    //         $statusHandler = function ($payload) {
    //             return StatusResponse::get($payload);
    //         };
    //     }

    //     return $statusHandler;
    // }

    /**
     * {@inheritdoc}
     */
    public function executeCommand(CommandInterface $command)
    {
        $raw = [
            'INFO',
            'zmpop',
            'ZUNION',
            'ZUNIONSTORE',
        ];

        try {
            if (in_array($command->getId(), $raw)) {
                return $this->reader->rawCommand(
                    $command->getId(),
                    ...$command->getArguments()
                );
            }

            return $this->reader->{$command->getId()}(
                ...$command->getArguments()
            );
        } catch (RelayException $ex) {
            $this->onCommandError($ex);
        }

        // if ($response instanceof ResponseInterface) {
        //     return $response;
        // }
    }

    /**
     * {@inheritdoc}
     */
    public function onCommandError(RelayException $exception)
    {
        $message = $exception->getMessage();

        if (strpos($message, 'RELAY_ERR_WRONGTYPE') && strpos($message, "Got reply-type 'status'")) {
            $message = 'Operation against a key holding the wrong kind of value';
        }

        return new ErrorResponse($message);
    }

    /**
     * {@inheritdoc}
     */
    public function read()
    {
        throw new NotSupportedException(
            'The "relay" extension does not support reading responses.'
        );
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
    public function __wakeup()
    {
        $this->assertExtensions();
        $this->reader = $this->createReader();
    }
}
