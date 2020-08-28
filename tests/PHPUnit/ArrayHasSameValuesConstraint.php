<?php

/*
 * This file is part of the Predis package.
 *
 * (c) Daniele Alessandri <suppakilla@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * PHPUnit constraint matching arrays with same elemnts even in different order.
 */
class ArrayHasSameValuesConstraint extends \PHPUnit\Framework\Constraint\Constraint
{
    protected $array;

    /**
     * @param array $array
     */
    public function __construct(array $array)
    {
        $this->array = $array;
    }

    /**
     * {@inheritdoc}
     */
    public function matches($other): bool
    {
        if (count($this->array) !== count($other)) {
            return false;
        }

        if (array_diff($this->array, $other)) {
            return false;
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function toString(): string
    {
        return 'two arrays contain the same elements.';
    }

    /**
     * {@inheritdoc}
     */
    protected function failureDescription($other): string
    {
        return $this->toString();
    }
}
