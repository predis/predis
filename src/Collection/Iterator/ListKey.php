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

use InvalidArgumentException;
use Iterator;
use Predis\ClientInterface;
use Predis\NotSupportedException;
use ReturnTypeWillChange;

/**
 * Abstracts the iteration of items stored in a list by leveraging the LRANGE
 * command wrapped in a fully-rewindable PHP iterator.
 *
 * This iterator tries to emulate the behaviour of cursor-based iterators based
 * on the SCAN-family of commands introduced in Redis <= 2.8, meaning that due
 * to its incremental nature with multiple fetches it can only offer limited
 * guarantees on the returned elements because the collection can change several
 * times (trimmed, deleted, overwritten) during the iteration process.
 *
 * @see http://redis.io/commands/lrange
 */
class ListKey implements Iterator
{
    protected $client;
    protected $count;
    protected $key;

    protected $valid;
    protected $fetchmore;
    protected $elements;
    protected $position;
    protected $current;

    /**
     * @param ClientInterface $client Client connected to Redis.
     * @param string          $key    Redis list key.
     * @param int             $count  Number of items retrieved on each fetch operation.
     *
     * @throws InvalidArgumentException
     */
    public function __construct(ClientInterface $client, $key, $count = 10)
    {
        $this->requiredCommand($client, 'LRANGE');

        if ((false === $count = filter_var($count, FILTER_VALIDATE_INT)) || $count < 0) {
            throw new InvalidArgumentException('The $count argument must be a positive integer.');
        }

        $this->client = $client;
        $this->key = $key;
        $this->count = $count;

        $this->reset();
    }

    /**
     * Ensures that the client instance supports the specified Redis command
     * required to fetch elements from the server to perform the iteration.
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
        $this->position = -1;
        $this->current = null;
    }

    /**
     * Fetches a new set of elements from the remote collection, effectively
     * advancing the iteration process.
     *
     * @return array
     */
    protected function executeCommand()
    {
        return $this->client->lrange($this->key, $this->position + 1, $this->position + $this->count);
    }

    /**
     * Populates the local buffer of elements fetched from the server during the
     * iteration.
     */
    protected function fetch()
    {
        $elements = $this->executeCommand();

        if (count($elements) < $this->count) {
            $this->fetchmore = false;
        }

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
     * @return void
     */
    #[ReturnTypeWillChange]
    public function rewind()
    {
        $this->reset();
        $this->next();
    }

    /**
     * @return mixed
     */
    #[ReturnTypeWillChange]
    public function current()
    {
        return $this->current;
    }

    /**
     * @return int|null
     */
    #[ReturnTypeWillChange]
    public function key()
    {
        return $this->position;
    }

    /**
     * @return void
     */
    #[ReturnTypeWillChange]
    public function next()
    {
        if (!$this->elements && $this->fetchmore) {
            $this->fetch();
        }

        if ($this->elements) {
            $this->extractNext();
        } else {
            $this->valid = false;
        }
    }

    /**
     * @return bool
     */
    #[ReturnTypeWillChange]
    public function valid()
    {
        return $this->valid;
    }
}
