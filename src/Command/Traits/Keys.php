<?php

namespace Predis\Command\Traits;

use Predis\Command\Command;
use UnexpectedValueException;

/**
 * @mixin Command
 */
trait Keys
{
    public function setArguments(array $arguments, bool $withNumkeys = true)
    {
        $argumentsLength = count($arguments);

        if (
            static::$keysArgumentPositionOffset > $argumentsLength ||
            !is_array($arguments[static::$keysArgumentPositionOffset])
        ) {
            throw new UnexpectedValueException('Wrong keys argument type or position offset');
        }

        $keysArgument = $arguments[static::$keysArgumentPositionOffset];
        $argumentsBeforeKeys = array_slice($arguments, 0, static::$keysArgumentPositionOffset);
        $argumentsAfterKeys = array_slice($arguments, static::$keysArgumentPositionOffset + 1);

        if ($withNumkeys) {
            $numkeys = count($keysArgument);
            parent::setArguments(array_merge($argumentsBeforeKeys, [$numkeys], $keysArgument, $argumentsAfterKeys));
            return;
        }

        parent::setArguments(array_merge($argumentsBeforeKeys, $keysArgument, $argumentsAfterKeys));
    }
}
