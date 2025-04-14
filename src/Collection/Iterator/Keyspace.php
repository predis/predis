<?php

/*
 * This file is part of the Predis package.
 *
 * (c) 2009-2020 Daniele Alessandri
 * (c) 2021-2025 Till Krüss
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Collection\Iterator;

use Predis\ClientInterface;

/**
 * Abstracts the iteration of the keyspace on a Redis instance by leveraging the
 * SCAN command (Redis >= 2.8) wrapped in a fully-rewindable PHP iterator.
 *
 * @see http://redis.io/commands/scan
 */
class Keyspace extends CursorBasedIterator
{
    /**
     * {@inheritdoc}
     */
    public function __construct(ClientInterface $client, $match = null, $count = null)
    {
        $this->requiredCommand($client, 'SCAN');

        parent::__construct($client, $match, $count);
    }

    /**
     * {@inheritdoc}
     */
    protected function executeCommand()
    {
        return $this->client->scan($this->cursor, $this->getScanOptions());
    }
}
