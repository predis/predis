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
