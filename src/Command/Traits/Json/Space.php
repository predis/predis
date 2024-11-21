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

namespace Predis\Command\Traits\Json;

use UnexpectedValueException;

trait Space
{
    private static $spaceModifier = 'SPACE';

    public function setArguments(array $arguments)
    {
        $argumentsLength = count($arguments);

        if (static::$spaceArgumentPositionOffset >= $argumentsLength) {
            parent::setArguments($arguments);

            return;
        }

        if ($arguments[static::$spaceArgumentPositionOffset] === '') {
            array_splice($arguments, static::$spaceArgumentPositionOffset, 1, [false]);
            parent::setArguments($arguments);

            return;
        }

        $argument = $arguments[static::$spaceArgumentPositionOffset];

        if (!is_string($argument)) {
            throw new UnexpectedValueException('Space argument value should be a string');
        }

        $argumentsBefore = array_slice($arguments, 0, static::$spaceArgumentPositionOffset);
        $argumentsAfter = array_slice($arguments, static::$spaceArgumentPositionOffset + 1);

        parent::setArguments(array_merge(
            $argumentsBefore,
            [self::$spaceModifier],
            [$argument],
            $argumentsAfter
        ));
    }
}
