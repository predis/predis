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

namespace Predis\Command\Container;

interface ContainerInterface
{
    /**
     * Creates Redis container command with subcommand as virtual method name
     * and sends a request to the server.
     *
<<<<<<<< HEAD:src/Command/Container/ContainerInterface.php
     * @param  string $subcommandID
     * @param  array  $arguments
========
     * @param        $subcommandID
     * @param        $arguments
>>>>>>>> e831a3ef3436647a0844e01141ae3d2d7ce5a7c3:src/Command/Redis/Container/ContainerInterface.php
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
