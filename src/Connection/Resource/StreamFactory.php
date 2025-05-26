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

namespace Predis\Connection\Resource;

use InvalidArgumentException;
use Predis\Connection\ParametersInterface;
use Predis\Connection\Resource\Exception\StreamInitException;
use Psr\Http\Message\StreamInterface;

class StreamFactory implements StreamFactoryInterface
{
    /**
     * {@inheritDoc}
     * @throws StreamInitException
     */
    public function createStream(ParametersInterface $parameters): StreamInterface
    {
        $parameters = $this->assertParameters($parameters);

        switch ($parameters->scheme) {
            case 'tcp':
            case 'redis':
                $stream = $this->tcpStreamInitializer($parameters);
                break;

            case 'unix':
                $stream = $this->unixStreamInitializer($parameters);
                break;

            case 'tls':
            case 'rediss':
                $stream = $this->tlsStreamInitializer($parameters);
                break;

            default:
                throw new InvalidArgumentException("Invalid scheme: '{$parameters->scheme}'.");
        }

        return new Stream($stream);
    }

    /**
     * Checks some parameters used to initialize the connection.
     *
     * @param ParametersInterface $parameters Initialization parameters for the connection.
     *
     * @return ParametersInterface
     * @throws InvalidArgumentException
     */
    protected function assertParameters(ParametersInterface $parameters): ParametersInterface
    {
        switch ($parameters->scheme) {
            case 'tcp':
            case 'redis':
            case 'unix':
            case 'tls':
            case 'rediss':
                break;

            default:
                throw new InvalidArgumentException("Invalid scheme: '$parameters->scheme'.");
        }

        return $parameters;
    }

    /**
     * Initializes a TCP stream resource.
     *
     * @param ParametersInterface $parameters Initialization parameters for the connection.
     *
     * @return resource
     * @throws StreamInitException
     */
    protected function tcpStreamInitializer(ParametersInterface $parameters)
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

        return $this->createStreamSocket($parameters, $address, $flags);
    }

    /**
     * Initializes a UNIX stream resource.
     *
     * @param ParametersInterface $parameters Initialization parameters for the connection.
     *
     * @return resource
     * @throws StreamInitException
     */
    protected function unixStreamInitializer(ParametersInterface $parameters)
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

        return $this->createStreamSocket($parameters, "unix://{$parameters->path}", $flags);
    }

    /**
     * Initializes a SSL-encrypted TCP stream resource.
     *
     * @param ParametersInterface $parameters Initialization parameters for the connection.
     *
     * @return resource
     * @throws StreamInitException
     */
    protected function tlsStreamInitializer(ParametersInterface $parameters)
    {
        $resource = $this->tcpStreamInitializer($parameters);
        $metadata = stream_get_meta_data($resource);

        // Detect if crypto mode is already enabled for this stream (PHP >= 7.0.0).
        if (isset($metadata['crypto'])) {
            return $resource;
        }

        if (isset($parameters->ssl) && is_array($parameters->ssl)) {
            $options = $parameters->ssl;
        } else {
            $options = [];
        }

        if (!isset($options['crypto_type'])) {
            $options['crypto_type'] = STREAM_CRYPTO_METHOD_TLS_CLIENT;
        }

        $context_options = function_exists('stream_context_set_options')
            ? stream_context_set_options($resource, ['ssl' => $options])
            : stream_context_set_option($resource, ['ssl' => $options]);

        if (!$context_options) {
            $this->onInitializationError($resource, $parameters, 'Error while setting SSL context options');
        }

        if (!stream_socket_enable_crypto($resource, true, $options['crypto_type'])) {
            $this->onInitializationError($resource, $parameters, 'Error while switching to encrypted communication');
        }

        return $resource;
    }

    /**
     * Creates a connected stream socket resource.
     *
     * @param ParametersInterface $parameters Connection parameters.
     * @param string              $address    Address for stream_socket_client().
     * @param int                 $flags      Flags for stream_socket_client().
     *
     * @return resource
     * @throws StreamInitException
     */
    protected function createStreamSocket(ParametersInterface $parameters, $address, $flags)
    {
        $timeout = (isset($parameters->timeout) ? (float) $parameters->timeout : 5.0);
        $context = stream_context_create(['socket' => ['tcp_nodelay' => (bool) $parameters->tcp_nodelay]]);

        if (
            (isset($parameters->persistent) && $parameters->persistent)
            && (isset($parameters->conn_uid) && $parameters->conn_uid)
        ) {
            $conn_uid = '/' . $parameters->conn_uid;
        } else {
            $conn_uid = '';
        }

        // Needs to create multiple persistent connections to the same resource
        $address = $address . $conn_uid;

        if (!$resource = @stream_socket_client($address, $errno, $errstr, $timeout, $flags, $context)) {
            $this->onInitializationError($resource, $parameters, trim($errstr), $errno);
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
     * Helper method to handle connection errors.
     *
     * @param  string              $message Error message.
     * @param  int                 $code    Error code.
     * @throws StreamInitException
     */
    protected function onInitializationError($stream, ParametersInterface $parameters, string $message, int $code = 0): void
    {
        if (is_resource($stream)) {
            fclose($stream);
        }

        throw new StreamInitException("$message [{$parameters}]", $code);
    }
}
