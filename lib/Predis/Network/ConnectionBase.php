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

use Predis\Helpers;
use Predis\IReplyObject;
use Predis\IConnectionParameters;
use Predis\ClientException;
use Predis\Commands\ICommand;
use Predis\Protocol\ProtocolException;

/**
 * Base class with the common logic used by connection classes to communicate with Redis.
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
abstract class ConnectionBase implements IConnectionSingle
{
    private $resource;
    private $cachedId;

    protected $parameters;
    protected $initCmds;

    /**
     * @param IConnectionParameters $parameters Parameters used to initialize the connection.
     */
    public function __construct(IConnectionParameters $parameters)
    {
        $this->initCmds = array();
        $this->parameters = $this->checkParameters($parameters);
        $this->initializeProtocol($parameters);
    }

    /**
     * Disconnects from the server and destroys the underlying resource when
     * PHP's garbage collector kicks in.
     */
    public function __destruct()
    {
        $this->disconnect();
    }

    /**
     * Checks some of the parameters used to initialize the connection.
     *
     * @param IConnectionParameters $parameters Parameters used to initialize the connection.
     */
    protected function checkParameters(IConnectionParameters $parameters)
    {
        switch ($parameters->scheme) {
            case 'unix':
                if (!isset($parameters->path)) {
                    throw new \InvalidArgumentException('Missing UNIX domain socket path');
                }

            case 'tcp':
                return $parameters;

            default:
                throw new \InvalidArgumentException("Invalid scheme: {$parameters->scheme}");
        }
    }

    /**
     * Initializes some common configurations of the underlying protocol processor
     * from the connection parameters.
     *
     * @param IConnectionParameters $parameters Parameters used to initialize the connection.
     */
    protected function initializeProtocol(IConnectionParameters $parameters)
    {
        // NOOP
    }

    /**
     * Creates the underlying resource used to communicate with Redis.
     *
     * @return mixed
     */
    protected abstract function createResource();

    /**
     * {@inheritdoc}
     */
    public function isConnected()
    {
        return isset($this->resource);
    }

    /**
     * {@inheritdoc}
     */
    public function connect()
    {
        if ($this->isConnected()) {
            throw new ClientException('Connection already estabilished');
        }
        $this->resource = $this->createResource();
    }

    /**
     * {@inheritdoc}
     */
    public function disconnect()
    {
        unset($this->resource);
    }

    /**
     * {@inheritdoc}
     */
    public function pushInitCommand(ICommand $command)
    {
        $this->initCmds[] = $command;
    }

    /**
     * {@inheritdoc}
     */
    public function executeCommand(ICommand $command)
    {
        $this->writeCommand($command);
        return $this->readResponse($command);
    }

    /**
     * {@inheritdoc}
     */
    public function readResponse(ICommand $command)
    {
        $reply = $this->read();

        if ($reply instanceof IReplyObject) {
            return $reply;
        }

        return $command->parseResponse($reply);
    }

    /**
     * Helper method to handle connection errors.
     *
     * @param string $message Error message.
     * @param int $code Error code.
     */
    protected function onConnectionError($message, $code = null)
    {
        Helpers::onCommunicationException(new ConnectionException($this, $message, $code));
    }

    /**
     * Helper method to handle protocol errors.
     *
     * @param string $message Error message.
     */
    protected function onProtocolError($message)
    {
        Helpers::onCommunicationException(new ProtocolException($this, $message));
    }

    /**
     * Helper method to handle invalid connection parameters.
     *
     * @param string $option Name of the option.
     * @param IConnectionParameters $parameters Parameters used to initialize the connection.
     */
    protected function onInvalidOption($option, $parameters = null)
    {
        $message = "Invalid option: $option";
        if (isset($parameters)) {
            $message .= " [$parameters]";
        }

        throw new \InvalidArgumentException($message);
    }

    /**
     * {@inheritdoc}
     */
    public function getResource()
    {
        if (isset($this->resource)) {
            return $this->resource;
        }

        $this->connect();

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
     * Gets an identifier for the connection.
     *
     * @return string
     */
    protected function getIdentifier()
    {
        if ($this->parameters->scheme === 'unix') {
            return $this->parameters->path;
        }

        return "{$this->parameters->host}:{$this->parameters->port}";
    }

    /**
     * {@inheritdoc}
     */
    public function __toString()
    {
        if (!isset($this->cachedId)) {
            $this->cachedId = $this->getIdentifier();
        }

        return $this->cachedId;
    }
}
