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
     * Returns containerId of specific container.
     *
     * @return string
     */
    public function getContainerId(): string;
}
