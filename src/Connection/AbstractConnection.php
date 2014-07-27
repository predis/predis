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

use InvalidArgumentException;
use Predis\CommunicationException;
use Predis\Command\CommandInterface;
use Predis\Protocol\ProtocolException;

/**
 * Base class with the common logic used by connection classes to communicate
 * with Redis.
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
abstract class AbstractConnection implements NodeConnectionInterface
{
    private $resource;
    private $cachedId;

    protected $parameters;
    protected $initCommands = array();

    /**
     * @param ParametersInterface $parameters Initialization parameters for the connection.
     */
    public function __construct(ParametersInterface $parameters)
    {
        $this->parameters = $this->assertParameters($parameters);
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
     * @param ParametersInterface $parameters Initialization parameters for the connection.
     *
     * @return ParametersInterface
     */
    protected function assertParameters(ParametersInterface $parameters)
    {
        $scheme = $parameters->scheme;

        if ($scheme !== 'tcp' && $scheme !== 'unix') {
            throw new InvalidArgumentException("Invalid scheme: '$scheme'.");
        }

        if ($scheme === 'unix' && !isset($parameters->path)) {
            throw new InvalidArgumentException('Missing UNIX domain socket path.');
        }

        return $parameters;
    }

    /**
     * Creates the underlying resource used to communicate with Redis.
     *
     * @return mixed
     */
    abstract protected function createResource();

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
        if (!$this->isConnected()) {
            $this->resource = $this->createResource();

            return true;
        }

        return false;
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
    public function addConnectCommand(CommandInterface $command)
    {
        $this->initCommands[] = $command;
    }

    /**
     * {@inheritdoc}
     */
    public function executeCommand(CommandInterface $command)
    {
        $this->writeRequest($command);

        return $this->readResponse($command);
    }

    /**
     * {@inheritdoc}
     */
    public function readResponse(CommandInterface $command)
    {
        return $this->read();
    }

    /**
     * Helper method to handle connection errors.
     *
     * @param string $message Error message.
     * @param int    $code    Error code.
     */
    protected function onConnectionError($message, $code = null)
    {
        CommunicationException::handle(
            new ConnectionException(
                $this, "$message [{$this->parameters->scheme}://{$this->getIdentifier()}]", $code
            )
        );
    }

    /**
     * Helper method to handle protocol errors.
     *
     * @param string $message Error message.
     */
    protected function onProtocolError($message)
    {
        CommunicationException::handle(
            new ProtocolException(
                $this, "$message [{$this->parameters->scheme}://{$this->getIdentifier()}]"
            )
        );
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

    /**
     * {@inheritdoc}
     */
    public function __sleep()
    {
        return array('parameters', 'initCommands');
    }
}
