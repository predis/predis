<?php

namespace Predis\Command\Traits;

use UnexpectedValueException;

/**
 * Resolves numkeys argument from keys and attach it to arguments
 */
trait Numkeys
{
    public $keysArgumentPositionOffset = 0;

    public function setArguments(array $arguments)
    {
        $argumentsLength = count($arguments);

        if (
            $this->keysArgumentPositionOffset > $argumentsLength ||
            !is_array($arguments[$this->keysArgumentPositionOffset])
        ) {
            throw new UnexpectedValueException('Wrong keys argument type or position offset');
        }

        $keysArgument = $arguments[$this->keysArgumentPositionOffset];
        $numkeys = count($keysArgument);
        $argumentsBeforeKeys = array_slice($arguments, 0, $this->keysArgumentPositionOffset);
        $argumentsAfterKeys = array_slice($arguments, $this->keysArgumentPositionOffset + 1);

        parent::setArguments(array_merge($argumentsBeforeKeys, [$numkeys], [$keysArgument], $argumentsAfterKeys));
    }
}
