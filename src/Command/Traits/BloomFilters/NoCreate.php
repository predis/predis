<?php

namespace Predis\Command\Traits\BloomFilters;

use Predis\Command\Command;
use UnexpectedValueException;

/**
 * @mixin Command
 */
trait NoCreate
{
    public function setArguments(array $arguments)
    {
        $argumentsLength = count($arguments);

        if (
            static::$noCreateArgumentPositionOffset >= $argumentsLength
            || false === $arguments[static::$noCreateArgumentPositionOffset]
        ) {
            parent::setArguments($arguments);

            return;
        }

        $argument = $arguments[static::$noCreateArgumentPositionOffset];

        if (true === $argument) {
            $argument = 'NOCREATE';
        } else {
            throw new UnexpectedValueException('Wrong NOCREATE argument type');
        }

        $argumentsBefore = array_slice($arguments, 0, static::$noCreateArgumentPositionOffset);
        $argumentsAfter = array_slice($arguments, static::$noCreateArgumentPositionOffset + 1);

        parent::setArguments(array_merge($argumentsBefore, [$argument], $argumentsAfter));
    }
}
