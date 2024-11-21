<?php

/*
 * This file is part of the Predis package.
 *
 * (c) 2009-2020 Daniele Alessandri
 * (c) 2021-2024 Till Krüss
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Response\Iterator;

use Countable;
use Iterator;
use Predis\Response\ResponseInterface;
use ReturnTypeWillChange;

/**
 * Iterator that abstracts the access to multibulk responses allowing them to be
 * consumed in a streamable fashion without keeping the whole payload in memory.
 *
 * This iterator does not support rewinding which means that the iteration, once
 * consumed, cannot be restarted.
 *
 * Always make sure that the whole iteration is consumed (or dropped) to prevent
 * protocol desynchronization issues.
 */
abstract class MultiBulkIterator implements Iterator, Countable, ResponseInterface
{
    protected $current;
    protected $position;
    protected $size;

    /**
     * @return void
     */
    #[ReturnTypeWillChange]
    public function rewind()
    {
        // NOOP
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
        if (++$this->position < $this->size) {
            $this->current = $this->getValue();
        }
    }

    /**
     * @return bool
     */
    #[ReturnTypeWillChange]
    public function valid()
    {
        return $this->position < $this->size;
    }

    /**
     * Returns the number of items comprising the whole multibulk response.
     *
     * This method should be used instead of iterator_count() to get the size of
     * the current multibulk response since the former consumes the iteration to
     * count the number of elements, but our iterators do not support rewinding.
     *
     * @return int
     */
    #[ReturnTypeWillChange]
    public function count()
    {
        return $this->size;
    }

    /**
     * Returns the current position of the iterator.
     *
     * @return int
     */
    public function getPosition()
    {
        return $this->position;
    }

    /**
     * {@inheritdoc}
     */
    abstract protected function getValue();
}
