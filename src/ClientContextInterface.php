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
 * Interface defining a client-side context such as a pipeline or transaction.
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
interface ClientContextInterface
{

    /**
     * Sends the specified command instance to Redis.
     *
     * @param  CommandInterface $command Command instance.
     * @return mixed
     */
    public function executeCommand(CommandInterface $command);

    /**
     * Sends the specified command with its arguments to Redis.
     *
     * @param  string $commandID Command ID.
     * @param  array  $arguments Arguments for the command.
     * @return mixed
     */
    public function __call($method, $arguments);

    /**
     * Starts the execution of the context.
     *
     * @param  mixed $callable Optional callback for execution.
     * @return array
     */
    public function execute($callable = null);
}
