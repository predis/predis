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
use Countable;
use Predis\ClientInterface;
use Predis\NotSupportedException;

/**
 * This class provides the base implementation for a fully-rewindable
 * PHP iterator that can incrementally iterate over collections stored
 * on Redis by leveraging the SCAN family of commands (Redis >= 2.8).
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 * @link http://redis.io/commands/scan
 */
abstract class AbstractScanIterator implements Iterator
{
    protected $client;
    protected $match;
    protected $count;

    protected $valid;
    protected $scanmore;
    protected $elements;
    protected $cursor;
    protected $position;
    protected $current;

    /**
     * @param ClientInterface $client Client connected to Redis.
     * @param string $match Pattern to match during the server-side iteration.
     * @param int $count Hints used by Redis to compute the number of results per iteration.
     */
    public function __construct(ClientInterface $client, $match = null, $count = null)
    {
        $this->client = $client;
        $this->match = $match;
        $this->count = $count;

        $this->reset();
    }

    /**
     * Ensures that the client instance supports the specified
     * Redis command required to perform the server-side iteration.
     *
     * @param ClientInterface Client connected to Redis.
     * @param string $commandID Command ID (e.g. `SCAN`).
     */
    protected function requiredCommand(ClientInterface $client, $commandID)
    {
        if (!$client->getProfile()->supportsCommand($commandID)) {
            throw new NotSupportedException("The specified server profile does not support the $commandID command.");
        }
    }

    /**
     * Resets the inner state of the iterator.
     */
    protected function reset()
    {
        $this->valid = true;
        $this->scanmore = true;
        $this->elements = array();
        $this->cursor = 0;
        $this->position = -1;
        $this->current = null;
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
    protected abstract function executeScanCommand();

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
     * Extracts next values for key() and current().
     */
    protected function extractNext()
    {
        $this->position++;
        $this->current = array_shift($this->elements);
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
            $this->extractNext();
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
