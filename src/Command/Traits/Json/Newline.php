<?php

namespace Predis\Command\Traits\Json;

use UnexpectedValueException;

trait Newline
{
    private static $newlineModifier = 'NEWLINE';

    public function setArguments(array $arguments)
    {
        $argumentsLength = count($arguments);

        if (static::$newlineArgumentPositionOffset >= $argumentsLength) {
            parent::setArguments($arguments);
            return;
        }

        if ($arguments[static::$newlineArgumentPositionOffset] === '') {
            array_splice($arguments, static::$newlineArgumentPositionOffset, 1, [false]);
            parent::setArguments($arguments);
            return;
        }

        $argument = $arguments[static::$newlineArgumentPositionOffset];

        if (!is_string($argument)) {
            throw new UnexpectedValueException('Newline argument value should be a string');
        }

        $argumentsBefore = array_slice($arguments, 0, static::$newlineArgumentPositionOffset);
        $argumentsAfter = array_slice($arguments,  static::$newlineArgumentPositionOffset + 1);

        parent::setArguments(array_merge(
            $argumentsBefore,
            [self::$newlineModifier],
            [$argument],
            $argumentsAfter
        ));
    }
}
