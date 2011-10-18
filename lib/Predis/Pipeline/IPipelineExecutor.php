<?php

/*
 * This file is part of the Predis package.
 *
 * (c) Daniele Alessandri <suppakilla@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Pipeline;

use Predis\Network\IConnection;

/**
 * Defines a strategy to write a list of commands to the network
 * and read back their replies.
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
interface IPipelineExecutor
{
    /**
     * Writes a list of commands to the network and reads back their replies.
     *
     * @param IConnection $connection Connection to Redis.
     * @param array $commands List of commands.
     * @return array
     */
    public function execute(IConnection $connection, &$commands);
}
