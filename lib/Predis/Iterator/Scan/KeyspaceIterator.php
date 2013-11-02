<?php

/*
 * This file is part of the Predis package.
 *
 * (c) Daniele Alessandri <suppakilla@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Iterator\Scan;

use Iterator;
use Predis\ClientInterface;
use Predis\NotSupportedException;

/**
 * Abstracts the iteration of the keyspace on a Redis instance
 * by leveraging the SCAN command (Redis >= 2.8) wrapped in a
 * fully-rewindable PHP iterator.
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 * @link http://redis.io/commands/scan
 */
class KeyspaceIterator implements Iterator
{
    protected $client;
    protected $match;
    protected $count;

    protected $valid;
    protected $scanmore;
    protected $elements;
    protected $position;
    protected $cursor;

    /**
     * @param ClientInterface $client Client connected to Redis.
     * @param string $match Pattern to match during the server-side iteration.
     * @param int $count Hints used by Redis to compute the number of results per iteration.
     */
    public function __construct(ClientInterface $client, $match = null, $count = null)
    {
        if (!$client->getProfile()->supportsCommand('SCAN')) {
            throw new NotSupportedException('The specified server profile does not support the SCAN command.');
        }

        $this->client = $client;
        $this->match = $match;
        $this->count = $count;

        $this->reset();
    }

    /**
     * Resets the inner state of the iterator.
     */
    protected function reset()
    {
        $this->valid = true;
        $this->scanmore = true;
        $this->elements = array();
        $this->position = -1;
        $this->cursor = 0;
    }

    /**
     * Returns an array of options for the SCAN command.
     *
     * @return array
     */
    protected function getScanOptions()
    {
        $options = array();

        if (strlen($this->match) > 0) {
            $options['MATCH'] = $this->match;
        }

        if ($this->count > 0) {
            $options['COUNT'] = $this->count;
        }

        return $options;
    }

    /**
     * Performs a new SCAN to fetch new elements in the collection from
     * Redis, effectively advancing the iteration process.
     *
     * @return array
     */
    protected function executeScanCommand()
    {
        return $this->client->scan($this->cursor, $this->getScanOptions());
    }

    /**
     * Populates the local buffer of elements fetched from the server
     * during the iteration.
     */
    protected function feed()
    {
        list($cursor, $elements) = $this->executeScanCommand();

        if (!$cursor) {
            $this->scanmore = false;
        }

        $this->cursor = $cursor;
        $this->elements = $elements;
    }

    /**
     * {@inheritdoc}
     */
    public function rewind()
    {
        $this->reset();
        $this->next();
    }

    /**
     * {@inheritdoc}
     */
    public function current()
    {
        return $this->current;
    }

    /**
     * {@inheritdoc}
     */
    public function key()
    {
        return $this->position;
    }

    /**
     * {@inheritdoc}
     */
    public function next()
    {
        if (!$this->elements && $this->scanmore) {
            $this->feed();
        }

        if ($this->elements) {
            $this->position++;
            $this->current = array_shift($this->elements);
        } else {
            $this->valid = false;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function valid()
    {
        return $this->valid;
    }
}
