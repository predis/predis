<?php

/*
 * This file is part of the Predis package.
 *
 * (c) 2009-2020 Daniele Alessandri
 * (c) 2021-2026 Till Krüss
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
trait Approx
{
    public function setArguments(array $arguments)
    {
        if (count($arguments) <= static::$approxArgumentPositionOffset || false === $arguments[static::$approxArgumentPositionOffset]) {
            parent::setArguments($arguments);

            return;
        }

        $argument = $arguments[static::$approxArgumentPositionOffset];

        if (true === $argument) {
            $argument = 'APPROX';
        } else {
            throw new UnexpectedValueException('Wrong approx argument type');
        }

        $argumentsBefore = array_slice($arguments, 0, static::$approxArgumentPositionOffset);
        $argumentsAfter = array_slice($arguments, static::$approxArgumentPositionOffset + 1);

        parent::setArguments(array_merge($argumentsBefore, [$argument], $argumentsAfter));
    }
}
