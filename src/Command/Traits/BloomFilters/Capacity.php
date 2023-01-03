<?php

namespace Predis\Command\Traits\BloomFilters;

use Predis\Command\Command;
use UnexpectedValueException;

/**
 * @mixin Command
 */
trait Capacity
{
    private static $capacityModifier = 'CAPACITY';

    public function setArguments(array $arguments)
    {
        $argumentsLength = count($arguments);

        if (static::$capacityArgumentPositionOffset >= $argumentsLength) {
            parent::setArguments($arguments);
            return;
        }

        if ($arguments[static::$capacityArgumentPositionOffset] === -1) {
            array_splice($arguments, static::$capacityArgumentPositionOffset, 1, [false]);
            parent::setArguments($arguments);
            return;
        }

        if ($arguments[static::$capacityArgumentPositionOffset] < 1) {
            throw new UnexpectedValueException('Wrong capacity argument value or position offset');
        }

        $argument = $arguments[static::$capacityArgumentPositionOffset];
        $argumentsBefore = array_slice($arguments, 0, static::$capacityArgumentPositionOffset);
        $argumentsAfter = array_slice($arguments, static::$capacityArgumentPositionOffset + 1);

        parent::setArguments(array_merge(
            $argumentsBefore,
            [self::$capacityModifier],
            [$argument],
            $argumentsAfter
        ));
    }
}
