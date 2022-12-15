<?php

namespace Predis\Command\Traits\With;

use Predis\Command\Command;
use UnexpectedValueException;

/**
 * @mixin Command
 */
trait WithCoord
{
    public function setArguments(array $arguments)
    {
        $argumentsLength = count($arguments);

        if (
            static::$withCoordArgumentPositionOffset >= $argumentsLength
            || false === $arguments[static::$withCoordArgumentPositionOffset]
        ) {
            parent::setArguments($arguments);
            return;
        }

        $argument = $arguments[static::$withCoordArgumentPositionOffset];

        if (true === $argument) {
            $argument = 'WITHCOORD';
        } else {
            throw new UnexpectedValueException("Wrong WITHCOORD argument type");
        }

        $argumentsBefore = array_slice($arguments, 0, static::$withCoordArgumentPositionOffset);
        $argumentsAfter = array_slice($arguments,  static::$withCoordArgumentPositionOffset + 1);

        parent::setArguments(array_merge($argumentsBefore, [$argument], $argumentsAfter));
    }
}
