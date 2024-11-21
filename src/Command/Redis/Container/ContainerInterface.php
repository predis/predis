<?php

/*
 * This file is part of the Predis package.
 *
 * (c) 2009-2020 Daniele Alessandri
 * (c) 2021-2024 Till Krüss
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
     * @param  string $subcommandID
     * @param  array  $arguments
     * @return mixed
     */
    public function __call(string $subcommandID, array $arguments);

    /**
     * Returns containerCommandId of specific container command.
     *
     * @return string
     */
    public function getContainerCommandId(): string;
}
