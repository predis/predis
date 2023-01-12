<?php

namespace Predis\Command\Redis\Container;

use Predis\ClientInterface;

abstract class AbstractContainer implements ContainerInterface
{
    /**
     * @var ClientInterface
     */
    protected $client;

    public function __construct(ClientInterface $client)
    {
        $this->client = $client;
    }

    /**
     * @inheritDoc
     */
    public function __call($commandID, $arguments)
    {
        array_unshift($arguments, strtoupper($commandID));

        return $this->client->executeCommand(
            $this->client->createCommand($this->getContainerId(), $arguments)
        );
    }

    /**
     * @inheritDoc
     */
    public function getContainerId(): string
    {
        return static::$containerId;
    }
}
