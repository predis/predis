<?php

/*
 * This file is part of the Predis package.
 *
 * (c) 2009-2020 Daniele Alessandri
 * (c) 2021-2023 Till Krüss
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Command\Traits\With;

use UnexpectedValueException;

trait WithHash
{
    public function setArguments(array $arguments)
    {
        $argumentsLength = count($arguments);

        if (
            static::$withHashArgumentPositionOffset >= $argumentsLength
            || false === $arguments[static::$withHashArgumentPositionOffset]
        ) {
            parent::setArguments($arguments);

            return;
        }

        $argument = $arguments[static::$withHashArgumentPositionOffset];

        if (true === $argument) {
            $argument = 'WITHHASH';
        } else {
            throw new UnexpectedValueException('Wrong WITHHASH argument type');
        }

        $argumentsBefore = array_slice($arguments, 0, static::$withHashArgumentPositionOffset);
        $argumentsAfter = array_slice($arguments, static::$withHashArgumentPositionOffset + 1);

        parent::setArguments(array_merge($argumentsBefore, [$argument], $argumentsAfter));
    }
}
