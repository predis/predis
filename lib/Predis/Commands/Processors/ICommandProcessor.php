<?php

/*
 * This file is part of the Predis package.
 *
 * (c) Daniele Alessandri <suppakilla@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Commands\Processors;

use Predis\Commands\ICommand;

/**
 * A command processor processes commands before they are sent to Redis.
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
interface ICommandProcessor
{
    /**
     * Processes a Redis command.
     *
     * @param ICommand $command Redis command.
     */
    public function process(ICommand $command);
}
