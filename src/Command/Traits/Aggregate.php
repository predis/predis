<?php

namespace Predis\Command\Traits;

use Predis\Command\Command;
use UnexpectedValueException;

/**
 * @mixin Command
 */
trait Aggregate
{
    /**
     * @var string[]
     */
    private static $aggregateValuesEnum = [
        'min' => 'MIN',
        'max' => 'MAX',
        'sum' => 'SUM'
    ];

    /**
     * @var string
     */
    private static $aggregateModifier = 'AGGREGATE';

    public function setArguments(array $arguments)
    {
        $argumentsLength = count($arguments);

        if (static::$aggregateArgumentPositionOffset >= $argumentsLength) {
            parent::setArguments($arguments);
            return;
        }

        $argument = $arguments[static::$aggregateArgumentPositionOffset];

        if (is_string($argument) && in_array(strtoupper($argument), self::$aggregateValuesEnum)) {
            $argument = self::$aggregateValuesEnum[$argument];
        } else {
            $enumValues = implode(', ', array_keys(self::$aggregateValuesEnum));
            throw new UnexpectedValueException("Aggregate argument accepts only: {$enumValues} values");
        }

        $argumentsBefore = array_slice($arguments, 0, static::$aggregateArgumentPositionOffset);
        $argumentsAfter = array_slice($arguments,  static::$aggregateArgumentPositionOffset + 1);

        parent::setArguments(array_merge(
            $argumentsBefore,
            [self::$aggregateModifier],
            [$argument],
            $argumentsAfter
        ));
    }
}
