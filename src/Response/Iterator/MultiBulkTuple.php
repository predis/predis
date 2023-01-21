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

namespace Predis\Response\Iterator;

use InvalidArgumentException;
use OuterIterator;
use ReturnTypeWillChange;
use UnexpectedValueException;

/**
 * Outer iterator consuming streamable multibulk responses by yielding tuples of
 * keys and values.
 *
 * This wrapper is useful for responses to commands such as `HGETALL` that can
 * be iterator as $key => $value pairs.
 */
class MultiBulkTuple extends MultiBulk implements OuterIterator
{
    private $iterator;

    /**
     * @param MultiBulk $iterator Inner multibulk response iterator.
     */
    public function __construct(MultiBulk $iterator)
    {
        $this->checkPreconditions($iterator);

        $this->size = count($iterator) / 2;
        $this->iterator = $iterator;
        $this->position = $iterator->getPosition();
        $this->current = $this->size > 0 ? $this->getValue() : null;
    }

    /**
     * Checks for valid preconditions.
     *
     * @param MultiBulk $iterator Inner multibulk response iterator.
     *
     * @throws InvalidArgumentException
     * @throws UnexpectedValueException
     */
    protected function checkPreconditions(MultiBulk $iterator)
    {
        if ($iterator->getPosition() !== 0) {
            throw new InvalidArgumentException(
                'Cannot initialize a tuple iterator using an already initiated iterator.'
            );
        }

        if (($size = count($iterator)) % 2 !== 0) {
            throw new UnexpectedValueException('Invalid response size for a tuple iterator.');
        }
    }

    /**
     * {@inheritdoc}
     */
    #[ReturnTypeWillChange]
    public function getInnerIterator()
    {
        return $this->iterator;
    }

    /**
     * {@inheritdoc}
     */
    public function __destruct()
    {
        $this->iterator->drop(true);
    }

    /**
     * {@inheritdoc}
     */
    protected function getValue()
    {
        $k = $this->iterator->current();
        $this->iterator->next();

        $v = $this->iterator->current();
        $this->iterator->next();

        return [$k, $v];
    }
}
