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
 * Abstracts the iteration of fields and values of an hash by leveraging the
 * HSCAN command (Redis >= 2.8) wrapped in a fully-rewindable PHP iterator.
 *
 * @see http://redis.io/commands/scan
 */
class HashKey extends CursorBasedIterator
{
    protected $key;

    /**
     * {@inheritdoc}
     */
    public function __construct(ClientInterface $client, $key, $match = null, $count = null)
    {
        $this->requiredCommand($client, 'HSCAN');

        parent::__construct($client, $match, $count);

        $this->key = $key;
    }

    /**
     * {@inheritdoc}
     */
    protected function executeCommand()
    {
        return $this->client->hscan($this->key, $this->cursor, $this->getScanOptions());
    }

    /**
     * {@inheritdoc}
     */
    protected function extractNext()
    {
        $this->position = key($this->elements);
        $this->current = current($this->elements);

        unset($this->elements[$this->position]);
    }
}
