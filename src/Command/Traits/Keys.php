<?php

namespace Predis\Command\Traits;

use Predis\Command\Command;
use UnexpectedValueException;

/**
 * @mixin Command
 */
trait Keys
{
    public function unpackKeysArray(int $keysArgumentOffset, array &$arguments): void
    {
        $argumentsLength = count($arguments);

        if ($keysArgumentOffset > $argumentsLength || !is_array($arguments[$keysArgumentOffset])) {
            throw new UnexpectedValueException('Wrong keys argument type or position offset');
        }

        $keysArgument = $arguments[$keysArgumentOffset];
        $argumentsBefore = array_slice($arguments, 0, $keysArgumentOffset);
        $argumentsAfter = array_slice($arguments,  ++$keysArgumentOffset);
        $arguments = array_merge($argumentsBefore, $keysArgument, $argumentsAfter);
    }
}
