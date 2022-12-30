<?php

namespace Predis\Command\Traits\BloomFilters;

use UnexpectedValueException;

trait Expansion
{
    private static $expansionModifier = 'EXPANSION';

    public function setArguments(array $arguments)
    {
        $argumentsLength = count($arguments);

        if (static::$expansionArgumentPositionOffset >= $argumentsLength) {
            parent::setArguments($arguments);
            return;
        }

        if ($arguments[static::$expansionArgumentPositionOffset] === -1) {
            array_splice($arguments, static::$expansionArgumentPositionOffset, 1, [false]);
            parent::setArguments($arguments);
            return;
        }

        if ($arguments[static::$expansionArgumentPositionOffset] < 1) {
            throw new UnexpectedValueException('Wrong expansion argument value or position offset');
        }

        $argument = $arguments[static::$expansionArgumentPositionOffset];
        $argumentsBefore = array_slice($arguments, 0, static::$expansionArgumentPositionOffset);
        $argumentsAfter = array_slice($arguments, static::$expansionArgumentPositionOffset + 1);

        parent::setArguments(array_merge(
            $argumentsBefore,
            [self::$expansionModifier],
            [$argument],
            $argumentsAfter
        ));
    }
}
