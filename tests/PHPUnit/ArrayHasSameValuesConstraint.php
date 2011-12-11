<?php

/*
 * This file is part of the Predis package.
 *
 * (c) Daniele Alessandri <suppakilla@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use \PHPUnit_Framework_Constraint;
use \PHPUnit_Framework_ExpectationFailedException;

/**
 * Constraint that accepts arrays with the same elements but different order.
 */
class ArrayHasSameValuesConstraint extends PHPUnit_Framework_Constraint
{
    protected $array;

    /**
     * @param array $array
     */
    public function __construct($array)
    {
        $this->array = $array;
    }

    /**
     * {@inheritdoc}
     */
    public function evaluate($other, $description = '', $returnResult = FALSE)
    {
        $description = $description ?: 'Failed asserting that two arrays have the same elements.';

        if (count($this->array) !== count($other)) {
            throw new PHPUnit_Framework_ExpectationFailedException($description);
        }
        if (array_diff($this->array, $other)) {
            throw new PHPUnit_Framework_ExpectationFailedException($description);
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function toString()
    {
        return 'two arrays have the same elements.';
    }
}