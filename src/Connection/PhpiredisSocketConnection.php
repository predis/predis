<?php

/*
 * This file is part of the Predis package.
 *
 * (c) Daniele Alessandri <suppakilla@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Connection;

use Predis\NotSupportedException;
use Predis\Command\CommandInterface;
use Predis\Response\Error as ErrorResponse;
use Predis\Response\Status as StatusResponse;

/**
 * This class provides the implementation of a Predis connection that uses the
 * PHP socket extension for network communication and wraps the phpiredis C
 * extension (PHP bindings for hiredis) to parse the Redis protocol.
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
 *  - scheme: it can be either 'tcp' or 'unix'.
 *  - host: hostname or IP address of the server.
 *  - port: TCP port of the server.
 *  - path: path of a UNIX domain socket when scheme is 'unix'.
 *  - timeout: timeout to perform the connection.
 *  - read_write_timeout: timeout of read / write operations.
 *
 * @link http://github.com/nrk/phpiredis
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class PhpiredisSocketConnection extends AbstractConnection
{
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
     * Disconnects from the server and destroys the underlying resource and the
     * protocol reader resource when PHP's garbage collector kicks in.
     */
    public function __destruct()
    {
        phpiredis_reader_destroy($this->reader);

        parent::__destruct();
    }

    /**
     * Checks if the socket and phpiredis extensions are loaded in PHP.
     */
    protected function assertExtensions()
    {
        if (!extension_loaded('sockets')) {
            throw new NotSupportedException(
                'The "sockets" extension is required by this connection backend.'
            );
        }

        if (!extension_loaded('phpiredis')) {
            throw new NotSupportedException(
                'The "phpiredis" extension is required by this connection backend.'
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function assertParameters(ParametersInterface $parameters)
    {
        if (isset($parameters->persistent)) {
            throw new NotSupportedException(
                "Persistent connections are not supported by this connection backend."
            );
        }

        return parent::assertParameters($parameters);
    }

    /**
     * Creates a new instance of the protocol reader resource.
     *
     * @return resource
     */
    private function createReader()
    {
        $reader = phpiredis_reader_create();

        phpiredis_reader_set_status_handler($reader, $this->getStatusHandler());
        phpiredis_reader_set_error_handler($reader, $this->getErrorHandler());

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

    /**
     * Returns the handler used by the protocol reader for inline responses.
     *
     * @return \Closure
     */
    private function getStatusHandler()
    {
        return function ($payload) {
            return StatusResponse::get($payload);
        };
    }

    /**
     * Returns the handler used by the protocol reader for error responses.
     *
     * @return \Closure
     */
    protected function getErrorHandler()
    {
        return function ($payload) {
            return new ErrorResponse($payload);
        };
    }

    /**
     * Helper method used to throw exceptions on socket errors.
     */
    private function emitSocketError()
    {
        $errno = socket_last_error();
        $errstr = socket_strerror($errno);

        $this->disconnect();

        $this->onConnectionError(trim($errstr), $errno);
    }

    /**
     * {@inheritdoc}
     */
    protected function createResource()
    {
        $isUnix = $this->parameters->scheme === 'unix';
        $domain = $isUnix ? AF_UNIX : AF_INET;
        $protocol = $isUnix ? 0 : SOL_TCP;

        $socket = @call_user_func('socket_create', $domain, SOCK_STREAM, $protocol);

        if (!is_resource($socket)) {
            $this->emitSocketError();
        }

        $this->setSocketOptions($socket, $this->parameters);

        return $socket;
    }

    /**
     * Sets options on the socket resource from the connection parameters.
     *
     * @param resource            $socket     Socket resource.
     * @param ParametersInterface $parameters Parameters used to initialize the connection.
     */
    private function setSocketOptions($socket, ParametersInterface $parameters)
    {
        if ($parameters->scheme !== 'tcp') {
            return;
        }

        if (!socket_set_option($socket, SOL_TCP, TCP_NODELAY, 1)) {
            $this->emitSocketError();
        }

        if (!socket_set_option($socket, SOL_SOCKET, SO_REUSEADDR, 1)) {
            $this->emitSocketError();
        }

        if (isset($parameters->read_write_timeout)) {
            $rwtimeout = (float) $parameters->read_write_timeout;
            $timeoutSec = floor($rwtimeout);
            $timeoutUsec = ($rwtimeout - $timeoutSec) * 1000000;

            $timeout = array(
                'sec' => $timeoutSec,
                'usec' => $timeoutUsec,
            );

            if (!socket_set_option($socket, SOL_SOCKET, SO_SNDTIMEO, $timeout)) {
                $this->emitSocketError();
            }

            if (!socket_set_option($socket, SOL_SOCKET, SO_RCVTIMEO, $timeout)) {
                $this->emitSocketError();
            }
        }
    }

    /**
     * Gets the address from the connection parameters.
     *
     * @param ParametersInterface $parameters Parameters used to initialize the connection.
     *
     * @return string
     */
    protected static function getAddress(ParametersInterface $parameters)
    {
        if ($parameters->scheme === 'unix') {
            return $parameters->path;
        }

        $host = $parameters->host;

        if (ip2long($host) === false) {
            if (false === $addresses = gethostbynamel($host)) {
                return false;
            }

            return $addresses[array_rand($addresses)];
        }

        return $host;
    }

    /**
     * Opens the actual connection to the server with a timeout.
     *
     * @param ParametersInterface $parameters Parameters used to initialize the connection.
     *
     * @return string
     */
    private function connectWithTimeout(ParametersInterface $parameters)
    {
        if (false === $host = self::getAddress($parameters)) {
            $this->onConnectionError("Cannot resolve the address of '$parameters->host'.");
        }

        $socket = $this->getResource();

        socket_set_nonblock($socket);

        if (@socket_connect($socket, $host, (int) $parameters->port) === false) {
            $error = socket_last_error();

            if ($error != SOCKET_EINPROGRESS && $error != SOCKET_EALREADY) {
                $this->emitSocketError();
            }
        }

        socket_set_block($socket);

        $null = null;
        $selectable = array($socket);

        $timeout = (float) $parameters->timeout;
        $timeoutSecs = floor($timeout);
        $timeoutUSecs = ($timeout - $timeoutSecs) * 1000000;

        $selected = socket_select($selectable, $selectable, $null, $timeoutSecs, $timeoutUSecs);

        if ($selected === 2) {
            $this->onConnectionError('Connection refused.', SOCKET_ECONNREFUSED);
        }
        if ($selected === 0) {
            $this->onConnectionError('Connection timed out.', SOCKET_ETIMEDOUT);
        }
        if ($selected === false) {
            $this->emitSocketError();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function connect()
    {
        if (parent::connect()) {
            $this->connectWithTimeout($this->parameters);

            if ($this->initCommands) {
                foreach ($this->initCommands as $command) {
                    $this->executeCommand($command);
                }
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function disconnect()
    {
        if ($this->isConnected()) {
            socket_close($this->getResource());
            parent::disconnect();
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function write($buffer)
    {
        $socket = $this->getResource();

        while (($length = strlen($buffer)) > 0) {
            $written = socket_write($socket, $buffer, $length);

            if ($length === $written) {
                return;
            }

            if ($written === false) {
                $this->onConnectionError('Error while writing bytes to the server.');
            }

            $buffer = substr($buffer, $written);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function read()
    {
        $socket = $this->getResource();
        $reader = $this->reader;

        while (PHPIREDIS_READER_STATE_INCOMPLETE === $state = phpiredis_reader_get_state($reader)) {
            if (@socket_recv($socket, $buffer, 4096, 0) === false || $buffer === '') {
                $this->emitSocketError();
            }

            phpiredis_reader_feed($reader, $buffer);
        }

        if ($state === PHPIREDIS_READER_STATE_COMPLETE) {
            return phpiredis_reader_get_reply($reader);
        } else {
            $this->onProtocolError(phpiredis_reader_get_error($reader));

            return;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function writeRequest(CommandInterface $command)
    {
        $arguments = $command->getArguments();
        array_unshift($arguments, $command->getId());

        $this->write(phpiredis_format_command($arguments));
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
