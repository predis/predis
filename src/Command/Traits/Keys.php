<?php

/*
 * This file is part of the Predis package.
 *
 * (c) 2009-2020 Daniele Alessandri
 * (c) 2021-2024 Till Krüss
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

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
            static::$keysArgumentPositionOffset > $argumentsLength
            || !is_array($arguments[static::$keysArgumentPositionOffset])
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
