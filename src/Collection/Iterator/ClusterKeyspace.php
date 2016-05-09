<?php

/*
 * This file is part of the Predis package.
 *
 * (c) Daniele Alessandri <suppakilla@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Collection\Iterator;

use Predis\ClientInterface;

/**
 * Abstracts the iteration of the keyspace on a Redis 3.0 Cluster by running the
 * SCAN command across all master nodes in turn.
 *
 * @author Chris Butler <chrisb@zedcore.com>
 *
 * @link http://redis.io/commands/scan
 */
class ClusterKeyspace extends CursorBasedIterator
{
    /** @var Iterator */
    private $connections;

    /** @var \Predis\ClientInterface */
    private $currentConnection;

    /**
     * {@inheritdoc}
     */
    public function __construct(ClientInterface $client, $match = null, $count = null)
    {
        $this->requiredCommand($client, 'SCAN');

        parent::__construct($client, $match, $count);
    }

    protected function reset()
    {
        parent::reset();
        $this->connections = $this->client->getConnection()->getIterator();
        $this->currentConnection = $this->connections->current();
    }

    /**
     * {@inheritdoc}
     */
    protected function executeCommand()
    {
        $command = $this->client->createCommand('scan', [max(0, $this->cursor), $this->getScanOptions()]);
        $result = $this->currentConnection->executeCommand($command);

        if (!$result[0])
        {
            // scan of the current client returned cursor of zero, move onto next connection on the next iteration
            $this->connections->next();
            $this->currentConnection = $this->connections->current();

            if ($this->currentConnection)
            {
                // if this scan returned no entries go for the next connection right now
                if (count($result[1]) == 0)
                {
                    return $this->executeCommand();
                }

                $result[0] = -1;
            }
        }

        return $result;
    }
}
