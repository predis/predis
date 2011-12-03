<?php

/*
 * This file is part of the Predis package.
 *
 * (c) Daniele Alessandri <suppakilla@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Network;

use Predis\Commands\ICommand;
use Predis\IConnectionParameters;
use Predis\ResponseError;
use Predis\ServerException;
use Predis\NotSupportedException;
use Predis\Protocol\ProtocolException;
use Predis\Network\ConnectionException;

const ERR_MSG_EXTENSION = 'The %s extension must be loaded in order to be able to use this connection class';

/**
 * This class implements a Predis connection that actually talks with Webdis
 * instead of connecting directly to Redis. It relies on the cURL extension to
 * communicate with the web server and the phpiredis extension to parse the
 * protocol of the replies returned in the http response bodies.
 *
 * Some features are not yet available or they simply cannot be implemented:
 *   - Pipelining commands.
 *   - Publish / Subscribe.
 *   - MULTI / EXEC transactions (not yet supported by Webdis).
 *
 * @link http://webd.is
 * @link http://github.com/nicolasff/webdis
 * @link http://github.com/seppo0010/phpiredis
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class WebdisConnection implements IConnectionSingle
{
    private $parameters;
    private $resource;
    private $reader;

    /**
     * @param IConnectionParameters $parameters Parameters used to initialize the connection.
     */
    public function __construct(IConnectionParameters $parameters)
    {
        $this->parameters = $parameters;

        if ($parameters->scheme !== 'http') {
            throw new \InvalidArgumentException("Invalid scheme: {$parameters->scheme}");
        }

        $this->checkExtensions();
        $this->resource = $this->initializeCurl($parameters);
        $this->reader = $this->initializeReader($parameters);
    }

    /**
     * Frees the underlying cURL and protocol reader resources when PHP's
     * garbage collector kicks in.
     */
    public function __destruct()
    {
        curl_close($this->resource);
        phpiredis_reader_destroy($this->reader);
    }

    /**
     * Helper method used to throw on unsupported methods.
     */
    private function throwNotSupportedException($function)
    {
        $class = __CLASS__;
        throw new NotSupportedException("The method $class::$function() is not supported");
    }

    /**
     * Checks if the cURL and phpiredis extensions are loaded in PHP.
     */
    private function checkExtensions()
    {
        if (!function_exists('curl_init')) {
            throw new NotSupportedException(sprintf(ERR_MSG_EXTENSION, 'curl'));
        }
        if (!function_exists('phpiredis_reader_create')) {
            throw new NotSupportedException(sprintf(ERR_MSG_EXTENSION, 'phpiredis'));
        }
    }

    /**
     * Initializes cURL.
     *
     * @param IConnectionParameters $parameters Parameters used to initialize the connection.
     * @return resource
     */
    private function initializeCurl(IConnectionParameters $parameters)
    {
        $options = array(
            CURLOPT_FAILONERROR => true,
            CURLOPT_CONNECTTIMEOUT_MS => $parameters->connection_timeout * 1000,
            CURLOPT_URL => "{$parameters->scheme}://{$parameters->host}:{$parameters->port}",
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_POST => true,
            CURLOPT_WRITEFUNCTION => array($this, 'feedReader'),
        );

        if (isset($parameters->user, $parameters->pass)) {
            $options[CURLOPT_USERPWD] = "{$parameters->user}:{$parameters->pass}";
        }

        $resource = curl_init();
        curl_setopt_array($resource, $options);

        return $resource;
    }

    /**
     * Initializes phpiredis' protocol reader.
     *
     * @param IConnectionParameters $parameters Parameters used to initialize the connection.
     * @return resource
     */
    private function initializeReader(IConnectionParameters $parameters)
    {
        $reader = phpiredis_reader_create();

        phpiredis_reader_set_status_handler($reader, $this->getStatusHandler());
        phpiredis_reader_set_error_handler($reader, $this->getErrorHandler($parameters->throw_errors));

        return $reader;
    }

    /**
     * Gets the handler used by the protocol reader to handle status replies.
     *
     * @return \Closure
     */
    protected function getStatusHandler()
    {
        return function($payload) {
            return $payload === 'OK' ? true : $payload;
        };
    }

    /**
     * Gets the handler used by the protocol reader to handle Redis errors.
     *
     * @param Boolean $throwErrors Specify if Redis errors throw exceptions.
     * @return \Closure
     */
    protected function getErrorHandler($throwErrors)
    {
        if ($throwErrors) {
            return function($errorMessage) {
                throw new ServerException($errorMessage);
            };
        }

        return function($errorMessage) {
            return new ResponseError($errorMessage);
        };
    }

    /**
     * Feeds phpredis' reader resource with the data read from the network.
     *
     * @param resource $resource Reader resource.
     * @param string $buffer Buffer with the reply read from the network.
     * @return int
     */
    protected function feedReader($resource, $buffer)
    {
        phpiredis_reader_feed($this->reader, $buffer);

        return strlen($buffer);
    }

    /**
     * {@inheritdoc}
     */
    public function connect()
    {
        // NOOP
    }

    /**
     * {@inheritdoc}
     */
    public function disconnect()
    {
        // NOOP
    }

    /**
     * {@inheritdoc}
     */
    public function isConnected()
    {
        return true;
    }

    /**
     * Checks if the specified command is supported by this connection class.
     *
     * @param ICommand $command The instance of a Redis command.
     * @return string
     */
    protected function getCommandId(ICommand $command)
    {
        switch (($commandId = $command->getId())) {
            case 'AUTH':
            case 'SELECT':
            case 'MULTI':
            case 'EXEC':
            case 'WATCH':
            case 'UNWATCH':
            case 'DISCARD':
            case 'MONITOR':
                throw new NotSupportedException("Disabled command: {$command->getId()}");

            default:
                return $commandId;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function writeCommand(ICommand $command)
    {
        $this->throwNotSupportedException(__FUNCTION__);
    }

    /**
     * {@inheritdoc}
     */
    public function readResponse(ICommand $command)
    {
        $this->throwNotSupportedException(__FUNCTION__);
    }

    /**
     * {@inheritdoc}
     */
    public function executeCommand(ICommand $command)
    {
        $resource = $this->resource;
        $commandId = $this->getCommandId($command);

        if ($arguments = $command->getArguments()) {
            $arguments = implode('/', array_map('urlencode', $arguments));
            $serializedCommand = "$commandId/$arguments.raw";
        }
        else {
            $serializedCommand = "$commandId.raw";
        }

        curl_setopt($resource, CURLOPT_POSTFIELDS, $serializedCommand);

        if (curl_exec($resource) === false) {
            $error = curl_error($resource);
            $errno = curl_errno($resource);
            throw new ConnectionException($this, trim($error), $errno);
        }

        $readerState = phpiredis_reader_get_state($this->reader);

        if ($readerState === PHPIREDIS_READER_STATE_COMPLETE) {
            $reply = phpiredis_reader_get_reply($this->reader);
            if ($reply instanceof IReplyObject) {
                return $reply;
            }
            return $command->parseResponse($reply);
        }
        else {
            $error = phpiredis_reader_get_error($this->reader);
            throw new ProtocolException($this, $error);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getResource()
    {
        return $this->resource;
    }

    /**
     * {@inheritdoc}
     */
    public function getParameters()
    {
        return $this->parameters;
    }

    /**
     * {@inheritdoc}
     */
    public function pushInitCommand(ICommand $command)
    {
        $this->throwNotSupportedException(__FUNCTION__);
    }

    /**
     * {@inheritdoc}
     */
    public function read()
    {
        $this->throwNotSupportedException(__FUNCTION__);
    }

    /**
     * {@inheritdoc}
     */
    public function __toString()
    {
        return "{$this->parameters->host}:{$this->parameters->port}";
    }
}
