<?php

/*
 * This file is part of the Predis package.
 *
 * (c) Daniele Alessandri <suppakilla@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Pipeline\Queue;

use Traversable;
use Predis\Connection\ConnectionInterface;

/**
 * Fire-and-forget command queue.
 *
 * This command queue simply writes enqueued commands to the target connection
 * and then ignores any response by dropping the underlying connection.
 */
class FireAndForget extends CommandQueue
{
    /**
     * @inheritdoc
     */
    public function flush(ConnectionInterface $connection): Traversable
    {
        $commands = $this->getQueue();
        $dequeued = count($commands);

        while (!$commands->isEmpty()) {
            $connection->writeRequest($commands->dequeue());
        }

        $connection->disconnect();

        yield from [];

        return $dequeued;
    }
}
