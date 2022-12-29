<?php

namespace Predis\Command\Traits;

use UnexpectedValueException;

trait Sorting
{
    private static $sortingEnum = [
        'asc' => 'ASC',
        'desc' => 'DESC',
    ];

    public function setArguments(array $arguments)
    {
        $argumentsLength = count($arguments);

        if (static::$sortArgumentPositionOffset >= $argumentsLength) {
            parent::setArguments($arguments);
            return;
        }

        $argument = $arguments[static::$sortArgumentPositionOffset];

        if (null === $argument) {
            array_splice($arguments, static::$sortArgumentPositionOffset, 1, [false]);
            parent::setArguments($arguments);
            return;
        }

        if (!in_array(strtoupper($argument), self::$sortingEnum, true)) {
            $enumValues = implode(', ', array_keys(self::$sortingEnum));
            throw new UnexpectedValueException("Sorting argument accepts only: {$enumValues} values");
        }

        $argumentsBefore = array_slice($arguments, 0, static::$sortArgumentPositionOffset);
        $argumentsAfter = array_slice($arguments,  static::$sortArgumentPositionOffset + 1);

        parent::setArguments(array_merge(
            $argumentsBefore,
            [self::$sortingEnum[$argument]],
            $argumentsAfter
        ));
    }
}
