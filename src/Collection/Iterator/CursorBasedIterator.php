<?php

/*
 * This file is part of the Predis package.
 *
 * (c) 2009-2020 Daniele Alessandri
 * (c) 2021-2023 Till Krüss
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Collection\Iterator;

use Iterator;
use Predis\ClientInterface;
use Predis\NotSupportedException;
use ReturnTypeWillChange;

/**
 * Provides the base implementation for a fully-rewindable PHP iterator that can
 * incrementally iterate over cursor-based collections stored on Redis using the
 * commands in the `SCAN` family.
 *
 * Given their incremental nature with multiple fetches, these kind of iterators
 * offer limited guarantees about the returned elements because the collection
 * can change several times during the iteration process.
 *
 * @see http://redis.io/commands/scan
 */
abstract class CursorBasedIterator implements Iterator
{
    protected $client;
    protected $match;
    protected $count;

    protected $valid;
    protected $fetchmore;
    protected $elements;
    protected $cursor;
    protected $position;
    protected $current;

    /**
     * @param ClientInterface $client Client connected to Redis.
     * @param string          $match  Pattern to match during the server-side iteration.
     * @param int             $count  Hint used by Redis to compute the number of results per iteration.
     */
    public function __construct(ClientInterface $client, $match = null, $count = null)
    {
        $this->client = $client;
        $this->match = $match;
        $this->count = $count;

        $this->reset();
    }

    /**
     * Ensures that the client supports the specified Redis command required to
     * fetch elements from the server to perform the iteration.
     *
     * @param ClientInterface $client    Client connected to Redis.
     * @param string          $commandID Command ID.
     *
     * @throws NotSupportedException
     */
    protected function requiredCommand(ClientInterface $client, $commandID)
    {
        if (!$client->getCommandFactory()->supports($commandID)) {
            throw new NotSupportedException("'$commandID' is not supported by the current command factory.");
        }
    }

    /**
     * Resets the inner state of the iterator.
     */
    protected function reset()
    {
        $this->valid = true;
        $this->fetchmore = true;
        $this->elements = [];
        $this->cursor = 0;
        $this->position = -1;
        $this->current = null;
    }

    /**
     * Returns an array of options for the `SCAN` command.
     *
     * @return array
     */
    protected function getScanOptions()
    {
        $options = [];

        if (strlen(strval($this->match)) > 0) {
            $options['MATCH'] = $this->match;
        }

        if ($this->count > 0) {
            $options['COUNT'] = $this->count;
        }

        return $options;
    }

    /**
     * Fetches a new set of elements from the remote collection, effectively
     * advancing the iteration process.
     *
     * @return array
     */
    abstract protected function executeCommand();

    /**
     * Populates the local buffer of elements fetched from the server during
     * the iteration.
     */
    protected function fetch()
    {
        [$cursor, $elements] = $this->executeCommand();

        if (!$cursor) {
            $this->fetchmore = false;
        }

        $this->cursor = $cursor;
        $this->elements = $elements;
    }

    /**
     * Extracts next values for key() and current().
     */
    protected function extractNext()
    {
        ++$this->position;
        $this->current = array_shift($this->elements);
    }

    /**
     * {@inheritdoc}
     */
    #[ReturnTypeWillChange]
    public function rewind()
    {
        $this->reset();
        $this->next();
    }

    /**
     * {@inheritdoc}
     */
    #[ReturnTypeWillChange]
    public function current()
    {
        return $this->current;
    }

    /**
     * {@inheritdoc}
     */
    #[ReturnTypeWillChange]
    public function key()
    {
        return $this->position;
    }

    /**
     * {@inheritdoc}
     */
    #[ReturnTypeWillChange]
    public function next()
    {
        tryFetch:
            if (!$this->elements && $this->fetchmore) {
                $this->fetch();
            }

            if ($this->elements) {
                $this->extractNext();
            } elseif ($this->cursor) {
                goto tryFetch;
            } else {
                $this->valid = false;
            }
    }

    /**
     * {@inheritdoc}
     */
    #[ReturnTypeWillChange]
    public function valid()
    {
        return $this->valid;
    }
}
