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

namespace PHPUnit;

use PHPUnit\Framework\Constraint\Constraint;

class OneOfConstraint extends Constraint
{
    /**
     * @var array
     */
    protected $array;

    public function __construct(array $array)
    {
        $this->array = $array;
    }

    /**
     * @param  mixed $other
     * @return bool
     */
    protected function matches($other): bool
    {
        if (is_array($other)) {
            return !empty(array_intersect($other, $this->array));
        }

        if (in_array($other, $this->array, true)) {
            return true;
        }

        return false;
    }

    /**
     * @param  mixed  $other
     * @return string
     */
    protected function failureDescription($other): string
    {
        return $this->toString();
    }

    /**
     * {@inheritDoc}
     */
    public function toString(): string
    {
        return 'given value matches any values from array';
    }
}
