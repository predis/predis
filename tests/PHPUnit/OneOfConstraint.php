<?php

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
     * @param mixed $other
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
     * @param $other
     * @return string
     */
    protected function failureDescription($other): string
    {
        return $this->toString();
    }

    /**
     * @inheritDoc
     */
    public function toString(): string
    {
        return 'given value matches any values from array';
    }
}
