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

use UnexpectedValueException;

trait Sorting
{
    private static $sortingEnum = [
        'asc' => 'ASC',
        'desc' => 'DESC',
    ];

    public function setArguments(array $arguments)
    {
        $argumentsLength = count($arguments);

        if (static::$sortArgumentPositionOffset >= $argumentsLength) {
            parent::setArguments($arguments);

            return;
        }

        $argument = $arguments[static::$sortArgumentPositionOffset];

        if (null === $argument) {
            array_splice($arguments, static::$sortArgumentPositionOffset, 1, [false]);
            parent::setArguments($arguments);

            return;
        }

        if (!in_array(strtoupper($argument), self::$sortingEnum, true)) {
            $enumValues = implode(', ', array_keys(self::$sortingEnum));
            throw new UnexpectedValueException("Sorting argument accepts only: {$enumValues} values");
        }

        $argumentsBefore = array_slice($arguments, 0, static::$sortArgumentPositionOffset);
        $argumentsAfter = array_slice($arguments, static::$sortArgumentPositionOffset + 1);

        parent::setArguments(array_merge(
            $argumentsBefore,
            [self::$sortingEnum[$argument]],
            $argumentsAfter
        ));
    }
}
