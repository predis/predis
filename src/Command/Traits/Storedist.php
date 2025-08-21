<?php

/*
 * This file is part of the Predis package.
 *
 * (c) 2009-2020 Daniele Alessandri
 * (c) 2021-2025 Till Krüss
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
trait Storedist
{
    public function setArguments(array $arguments)
    {
        $argumentsLength = count($arguments);

        if (
            static::$storeDistArgumentPositionOffset >= $argumentsLength
            || false === $arguments[static::$storeDistArgumentPositionOffset]
        ) {
            parent::setArguments($arguments);

            return;
        }

        $argument = $arguments[static::$storeDistArgumentPositionOffset];

        if (true === $argument) {
            $argument = 'STOREDIST';
        } else {
            throw new UnexpectedValueException('Wrong STOREDIST argument type');
        }

        $argumentsBefore = array_slice($arguments, 0, static::$storeDistArgumentPositionOffset);
        $argumentsAfter = array_slice($arguments, static::$storeDistArgumentPositionOffset + 1);

        parent::setArguments(array_merge($argumentsBefore, [$argument], $argumentsAfter));
    }
}
