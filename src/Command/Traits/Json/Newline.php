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

namespace Predis\Command\Traits\Json;

use UnexpectedValueException;

trait Newline
{
    private static $newlineModifier = 'NEWLINE';

    public function setArguments(array $arguments)
    {
        $argumentsLength = count($arguments);

        if (static::$newlineArgumentPositionOffset >= $argumentsLength) {
            parent::setArguments($arguments);

            return;
        }

        if ($arguments[static::$newlineArgumentPositionOffset] === '') {
            array_splice($arguments, static::$newlineArgumentPositionOffset, 1, [false]);
            parent::setArguments($arguments);

            return;
        }

        $argument = $arguments[static::$newlineArgumentPositionOffset];

        if (!is_string($argument)) {
            throw new UnexpectedValueException('Newline argument value should be a string');
        }

        $argumentsBefore = array_slice($arguments, 0, static::$newlineArgumentPositionOffset);
        $argumentsAfter = array_slice($arguments, static::$newlineArgumentPositionOffset + 1);

        parent::setArguments(array_merge(
            $argumentsBefore,
            [self::$newlineModifier],
            [$argument],
            $argumentsAfter
        ));
    }
}
