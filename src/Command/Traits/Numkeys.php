<?php

namespace Predis\Command\Traits;

use Predis\Command\Command;
use UnexpectedValueException;

/**
 * Resolves numkeys argument from keys and attach it to arguments
 *
 * @mixin Command
 */
trait Numkeys
{
    public function setArguments(array $arguments)
    {
        $argumentsLength = count($arguments);

        if (
            static::$keysArgumentPositionOffset > $argumentsLength ||
            !is_array($arguments[static::$keysArgumentPositionOffset])
        ) {
            throw new UnexpectedValueException('Wrong keys argument type or position offset');
        }

        $keysArgument = $arguments[static::$keysArgumentPositionOffset];
        $numkeys = count($keysArgument);
        $argumentsBeforeKeys = array_slice($arguments, 0, static::$keysArgumentPositionOffset);
        $argumentsAfterKeys = array_slice($arguments, static::$keysArgumentPositionOffset + 1);

        parent::setArguments(array_merge($argumentsBeforeKeys, [$numkeys], [$keysArgument], $argumentsAfterKeys));
    }
}
