<?php

namespace Predis\Command\Redis\Container;

interface ContainerInterface
{
    /**
     * Creates Redis container command with subcommand as virtual method name
     * and sends a request to the server.
     *
     * @param $commandID
     * @param $arguments
     * @return mixed
     */
    public function __call($commandID, $arguments);

    /**
     * Returns containerId of specific container
     *
     * @return string
     */
    public function getContainerId(): string;
}
