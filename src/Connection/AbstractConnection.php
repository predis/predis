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

use Predis\Command\CommandInterface;
use Predis\Command\RawCommand;
use Predis\CommunicationException;
use Predis\Connection\Resource\Exception\StreamInitException;
use Predis\Protocol\Parser\ParserStrategyResolver;
use Predis\Protocol\Parser\Strategy\ParserStrategyInterface;
use Predis\Protocol\ProtocolException;
use Predis\TimeoutException;

/**
 * Base class with the common logic used by connection classes to communicate
 * with Redis.
 */
abstract class AbstractConnection implements NodeConnectionInterface
{
    /**
     * @var ParserStrategyInterface
     */
    protected $parserStrategy;

    /**
     * @var int|null
     */
    protected $clientId;

    protected $resource;
    private $cachedId;

    protected $parameters;

    /**
     * @var RawCommand[]
     */
    protected $initCommands = [];

    /**
     * @param ParametersInterface $parameters Initialization parameters for the connection.
     */
    public function __construct(ParametersInterface $parameters)
    {
        $this->parameters = $parameters;
        $this->setParserStrategy();
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
     * {@inheritdoc}
     */
    public function isConnected()
    {
        return isset($this->resource);
    }

    /**
     * {@inheritdoc}
     */
    public function hasDataToRead(): bool
    {
        return true;
    }

    /**
     * Creates a stream resource to communicate with Redis.
     *
     * @return mixed
     * @throws StreamInitException
     */
    abstract protected function createResource();

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
    public function getInitCommands(): array
    {
        return $this->initCommands;
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
     * @param  string                 $message Error message.
     * @param  int                    $code    Error code.
     * @throws CommunicationException
     */
    protected function onConnectionError($message, $code = 0): void
    {
        CommunicationException::handle(
            new ConnectionException($this, "$message [{$this->getParameters()}]", $code)
        );
    }

    /**
     * Helper method to handle timeout errors.
     *
     * @param  int                    $code
     * @return void
     * @throws CommunicationException
     */
    protected function onTimeoutError(int $code = 0): void
    {
        CommunicationException::handle(
            new TimeoutException($this, $code)
        );
    }

    /**
     * Helper method to handle protocol errors.
     *
     * @param  string                 $message Error message.
     * @throws CommunicationException
     */
    protected function onProtocolError($message)
    {
        CommunicationException::handle(
            new ProtocolException($this, "$message [{$this->getParameters()}]")
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
     * {@inheritDoc}
     */
    public function getClientId(): ?int
    {
        return $this->clientId;
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
        return ['parameters', 'initCommands'];
    }

    /**
     * Set parser strategy for given connection.
     *
     * @return void
     */
    protected function setParserStrategy(): void
    {
        $strategyResolver = new ParserStrategyResolver();
        $this->parserStrategy = $strategyResolver->resolve((int) $this->parameters->protocol);
    }
}
