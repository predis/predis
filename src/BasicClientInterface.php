<?php

/*
 * This file is part of the Predis package.
 *
 * (c) Daniele Alessandri <suppakilla@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis;

use Predis\Command\CommandInterface;

/**
 * Defines the interface of a basic client class or abstraction that can send
 * commands to Redis.
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
interface BasicClientInterface
{
    /**
     * Executes the specified Redis command.
     *
     * @param  CommandInterface $command Command instance.
     * @return mixed
     */
    public function executeCommand(CommandInterface $command);
}
