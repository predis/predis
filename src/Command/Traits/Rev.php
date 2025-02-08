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
trait Rev
{
    public function setArguments(array $arguments)
    {
        $argument = $arguments[static::$revArgumentPositionOffset];

        if (false === $argument) {
            parent::setArguments($arguments);

            return;
        }

        if (true === $argument) {
            $argument = 'REV';
        } else {
            throw new UnexpectedValueException('Wrong rev argument type');
        }

        $argumentsBefore = array_slice($arguments, 0, static::$revArgumentPositionOffset);
        $argumentsAfter = array_slice($arguments, static::$revArgumentPositionOffset + 1);

        parent::setArguments(array_merge($argumentsBefore, [$argument], $argumentsAfter));
    }
}
