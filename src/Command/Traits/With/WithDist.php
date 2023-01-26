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
            throw new UnexpectedValueException('Wrong WITHDIST argument type');
        }

        $argumentsBefore = array_slice($arguments, 0, static::$withDistArgumentPositionOffset);
        $argumentsAfter = array_slice($arguments, static::$withDistArgumentPositionOffset + 1);

        parent::setArguments(array_merge($argumentsBefore, [$argument], $argumentsAfter));
    }
}
