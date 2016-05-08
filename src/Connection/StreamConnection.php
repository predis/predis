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

use Predis\Command\CommandInterface;
use Predis\Response\Error as ErrorResponse;
use Predis\Response\Status as StatusResponse;

/**
 * Standard connection to Redis servers implemented on top of PHP's streams.
 * The connection parameters supported by this class are:.
 *
 *  - scheme: it can be either 'redis', 'tcp' or 'unix'.
 *  - host: hostname or IP address of the server.
 *  - port: TCP port of the server.
 *  - path: path of a UNIX domain socket when scheme is 'unix'.
 *  - timeout: timeout to perform the connection.
 *  - read_write_timeout: timeout of read / write operations.
 *  - async_connect: performs the connection asynchronously.
 *  - tcp_nodelay: enables or disables Nagle's algorithm for coalescing.
 *  - persistent: the connection is left intact after a GC collection.
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class StreamConnection extends AbstractConnection
{
    /**
     * Disconnects from the server and destroys the underlying resource when the
     * garbage collector kicks in only if the connection has not been marked as
     * persistent.
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
    protected function createResource()
    {
        switch ($this->parameters->scheme) {
            case 'tcp':
            case 'redis':
                return $this->tcpStreamInitializer($this->parameters);

            case 'unix':
                return $this->unixStreamInitializer($this->parameters);

            default:
                throw new \InvalidArgumentException("Invalid scheme: '{$this->parameters->scheme}'.");
        }
    }

    /**
     * Initializes a TCP stream resource.
     *
     * @param ParametersInterface $parameters Initialization parameters for the connection.
     *
     * @return resource
     */
    protected function tcpStreamInitializer(ParametersInterface $parameters)
    {
        if (!filter_var($parameters->host, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            $uri = "tcp://$parameters->host:$parameters->port";
        } else {
            $uri = "tcp://[$parameters->host]:$parameters->port";
        }

        $flags = STREAM_CLIENT_CONNECT;

        if (isset($parameters->async_connect) && (bool) $parameters->async_connect) {
            $flags |= STREAM_CLIENT_ASYNC_CONNECT;
        }

        if (isset($parameters->persistent) && (bool) $parameters->persistent) {
            $flags |= STREAM_CLIENT_PERSISTENT;
            $uri .= strpos($path = $parameters->path, '/') === 0 ? $path : "/$path";
        }

        $resource = @stream_socket_client($uri, $errno, $errstr, (float) $parameters->timeout, $flags);

        if (!$resource) {
            $this->onConnectionError(trim($errstr), $errno);
        }

        if (isset($parameters->read_write_timeout)) {
            $rwtimeout = (float) $parameters->read_write_timeout;
            $rwtimeout = $rwtimeout > 0 ? $rwtimeout : -1;
            $timeoutSeconds = floor($rwtimeout);
            $timeoutUSeconds = ($rwtimeout - $timeoutSeconds) * 1000000;
            stream_set_timeout($resource, $timeoutSeconds, $timeoutUSeconds);
        }

        if (isset($parameters->tcp_nodelay) && function_exists('socket_import_stream')) {
            $socket = socket_import_stream($resource);
            socket_set_option($socket, SOL_TCP, TCP_NODELAY, (int) $parameters->tcp_nodelay);
        }

        return $resource;
    }

    /**
     * Initializes a UNIX stream resource.
     *
     * @param ParametersInterface $parameters Initialization parameters for the connection.
     *
     * @return resource
     */
    protected function unixStreamInitializer(ParametersInterface $parameters)
    {
        if (!isset($parameters->path)) {
            throw new \InvalidArgumentException('Missing UNIX domain socket path.');
        }

        $uri = "unix://{$parameters->path}";
        $flags = STREAM_CLIENT_CONNECT;

        if ((bool) $parameters->persistent) {
            $flags |= STREAM_CLIENT_PERSISTENT;
        }

        $resource = @stream_socket_client($uri, $errno, $errstr, (float) $parameters->timeout, $flags);

        if (!$resource) {
            $this->onConnectionError(trim($errstr), $errno);
        }

        if (isset($parameters->read_write_timeout)) {
            $rwtimeout = (float) $parameters->read_write_timeout;
            $rwtimeout = $rwtimeout > 0 ? $rwtimeout : -1;
            $timeoutSeconds = floor($rwtimeout);
            $timeoutUSeconds = ($rwtimeout - $timeoutSeconds) * 1000000;
            stream_set_timeout($resource, $timeoutSeconds, $timeoutUSeconds);
        }

        return $resource;
    }

    /**
     * {@inheritdoc}
     */
    public function connect()
    {
        if (parent::connect() && $this->initCommands) {
            foreach ($this->initCommands as $command) {
                $this->executeCommand($command);
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function disconnect()
    {
        if ($this->isConnected()) {
            fclose($this->getResource());
            parent::disconnect();
        }
    }

    /**
     * Performs a write operation over the stream of the buffer containing a
     * command serialized with the Redis wire protocol.
     *
     * @param string $buffer Representation of a command in the Redis wire protocol.
     */
    protected function write($buffer)
    {
        $socket = $this->getResource();

        while (($length = strlen($buffer)) > 0) {
            $written = @fwrite($socket, $buffer);

            if ($length === $written) {
                return;
            }

            if ($written === false || $written === 0) {
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
        $chunk = fgets($socket);

        if ($chunk === false || $chunk === '') {
            $this->onConnectionError('Error while reading line from the server.');
        }

        $prefix = $chunk[0];
        $payload = substr($chunk, 1, -2);

        switch ($prefix) {
            case '+':
                return StatusResponse::get($payload);

            case '$':
                $size = (int) $payload;

                if ($size === -1) {
                    return;
                }

                $bulkData = '';
                $bytesLeft = ($size += 2);

                do {
                    $chunk = fread($socket, min($bytesLeft, 4096));

                    if ($chunk === false || $chunk === '') {
                        $this->onConnectionError('Error while reading bytes from the server.');
                    }

                    $bulkData .= $chunk;
                    $bytesLeft = $size - strlen($bulkData);
                } while ($bytesLeft > 0);

                return substr($bulkData, 0, -2);

            case '*':
                $count = (int) $payload;

                if ($count === -1) {
                    return;
                }

                $multibulk = array();

                for ($i = 0; $i < $count; ++$i) {
                    $multibulk[$i] = $this->read();
                }

                return $multibulk;

            case ':':
                return (int) $payload;

            case '-':
                return new ErrorResponse($payload);

            default:
                $this->onProtocolError("Unknown response prefix: '$prefix'.");

                return;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function writeRequest(CommandInterface $command)
    {
        $commandID = $command->getId();
        $arguments = $command->getArguments();

        $cmdlen = strlen($commandID);
        $reqlen = count($arguments) + 1;

        $buffer = "*{$reqlen}\r\n\${$cmdlen}\r\n{$commandID}\r\n";

        foreach ($arguments as $argument) {
            $arglen = strlen($argument);
            $buffer .= "\${$arglen}\r\n{$argument}\r\n";
        }

        $this->write($buffer);
    }
}
