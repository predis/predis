<?php

namespace Predis\Command\Traits;

use Predis\Command\Command;
use UnexpectedValueException;

/**
 * @mixin Command
 */
trait Limit
{
    public function setArguments(array $arguments)
    {
        $argument = $arguments[static::$limitArgumentPositionOffset];
        $argumentsBefore = array_slice($arguments, 0, static::$limitArgumentPositionOffset);

        if (false === $argument) {
            parent::setArguments($argumentsBefore);
            return;
        }

        if (true === $argument) {
            $argument = 'LIMIT';
        } else {
            throw new UnexpectedValueException('Wrong limit argument type');
        }

        $argumentsAfter = array_slice($arguments,  static::$limitArgumentPositionOffset + 1);

        parent::setArguments(array_merge($argumentsBefore, [$argument], $argumentsAfter));
    }
}
