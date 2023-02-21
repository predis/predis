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

namespace Predis\Command\Traits\Limit;

use Predis\Command\Command;
use UnexpectedValueException;

/**
 * @mixin Command
 */
trait Limit
{
    private static $limitModifier = 'LIMIT';

    public function setArguments(array $arguments)
    {
        $argumentsLength = count($arguments);
        $argumentsBefore = array_slice($arguments, 0, static::$limitArgumentPositionOffset);

        if (
            static::$limitArgumentPositionOffset >= $argumentsLength
            || false === $arguments[static::$limitArgumentPositionOffset]
        ) {
            parent::setArguments($argumentsBefore);

            return;
        }

        $argument = $arguments[static::$limitArgumentPositionOffset];
        $argumentsAfter = array_slice($arguments, static::$limitArgumentPositionOffset + 1);

        if (true === $argument) {
            parent::setArguments(array_merge($argumentsBefore, [self::$limitModifier], $argumentsAfter));

            return;
        }

        if (!is_int($argument)) {
            throw new UnexpectedValueException('Wrong limit argument type');
        }

        parent::setArguments(array_merge($argumentsBefore, [self::$limitModifier], [$argument], $argumentsAfter));
    }
}
