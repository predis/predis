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

class AssertSameWithPrecisionConstraint extends Constraint
{
    /**
     * @var mixed
     */
    private $expectedValue;

    /**
     * @var int
     */
    private $precision;

    public function __construct($expectedValue, int $precision)
    {
        $this->expectedValue = $expectedValue;
        $this->precision = $precision;
    }

    /**
     * {@inheritdoc}
     */
    public function matches($other): bool
    {
        if (gettype($this->expectedValue) !== gettype($other)) {
            return false;
        }

        if (is_array($other)) {
            $other = array_map([$this, 'roundToPrecision'], $other);
            $this->expectedValue = array_map([$this, 'roundToPrecision'], $this->expectedValue);

            return !array_diff($this->expectedValue, $other);
        }

        $other = $this->roundToPrecision($other);
        $this->expectedValue = $this->roundToPrecision($this->expectedValue);

        return $other === $this->expectedValue;
    }

    /**
     * {@inheritDoc}
     */
    public function toString(): string
    {
        return 'given value matches another value with given precision';
    }

    /**
     * {@inheritdoc}
     */
    protected function failureDescription($other): string
    {
        return $this->toString();
    }

    /**
     * @param  mixed $numeric
     * @return float
     */
    private function roundToPrecision($numeric): float
    {
        return round((float) $numeric, $this->precision);
    }
}
