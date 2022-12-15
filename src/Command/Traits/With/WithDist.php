<?php

namespace Predis\Command\Traits\With;

use UnexpectedValueException;

trait WithDist
{
    public function setArguments(array $arguments)
    {
        $argumentsLength = count($arguments);

        if (
            static::$withDistArgumentPositionOffset >= $argumentsLength
            || false === $arguments[static::$withDistArgumentPositionOffset]
        ) {
            parent::setArguments($arguments);
            return;
        }

        $argument = $arguments[static::$withDistArgumentPositionOffset];

        if (true === $argument) {
            $argument = 'WITHDIST';
        } else {
            throw new UnexpectedValueException("Wrong WITHDIST argument type");
        }

        $argumentsBefore = array_slice($arguments, 0, static::$withDistArgumentPositionOffset);
        $argumentsAfter = array_slice($arguments,  static::$withDistArgumentPositionOffset + 1);

        parent::setArguments(array_merge($argumentsBefore, [$argument], $argumentsAfter));
    }
}
