<?php

namespace Predis\Command\Traits;

use UnexpectedValueException;

trait Timeout
{
    private static $timeoutModifier = 'TIMEOUT';

    public function setArguments(array $arguments)
    {
        $argumentsLength = count($arguments);

        if (static::$timeoutArgumentPositionOffset >= $argumentsLength) {
            parent::setArguments($arguments);
            return;
        }

        if ($arguments[static::$timeoutArgumentPositionOffset] === -1) {
            array_splice($arguments, static::$timeoutArgumentPositionOffset, 1, [false]);
            parent::setArguments($arguments);
            return;
        }

        if ($arguments[static::$timeoutArgumentPositionOffset] < 1) {
            throw new UnexpectedValueException('Wrong timeout argument value or position offset');
        }

        $argument = $arguments[static::$timeoutArgumentPositionOffset];
        $argumentsBefore = array_slice($arguments, 0, static::$timeoutArgumentPositionOffset);
        $argumentsAfter = array_slice($arguments, static::$timeoutArgumentPositionOffset + 1);

        parent::setArguments(array_merge(
            $argumentsBefore,
            [self::$timeoutModifier],
            [$argument],
            $argumentsAfter
        ));
    }
}
