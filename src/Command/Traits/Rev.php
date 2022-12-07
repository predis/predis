<?php

namespace Predis\Command\Traits;

use Predis\Command\Command;
use UnexpectedValueException;

/**
 * @mixin Command
 */
trait Rev
{
    public function setArguments(array $arguments)
    {
        $argument = $arguments[static::$revArgumentPositionOffset];

        if (false === $argument) {
            parent::setArguments($arguments);
            return;
        }

        if (true === $argument) {
            $argument = 'REV';
        } else {
            throw new UnexpectedValueException('Wrong rev argument type');
        }

        $argumentsBefore = array_slice($arguments, 0, static::$revArgumentPositionOffset);
        $argumentsAfter = array_slice($arguments,  static::$revArgumentPositionOffset + 1);

        parent::setArguments(array_merge($argumentsBefore, [$argument], $argumentsAfter));
    }
}
