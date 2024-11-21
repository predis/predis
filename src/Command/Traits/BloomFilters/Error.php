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

namespace Predis\Command\Traits\BloomFilters;

use Predis\Command\Command;
use UnexpectedValueException;

/**
 * @mixin Command
 */
trait Error
{
    private static $errorModifier = 'ERROR';

    public function setArguments(array $arguments)
    {
        $argumentsLength = count($arguments);

        if (static::$errorArgumentPositionOffset >= $argumentsLength) {
            parent::setArguments($arguments);

            return;
        }

        if ($arguments[static::$errorArgumentPositionOffset] === -1) {
            array_splice($arguments, static::$errorArgumentPositionOffset, 1, [false]);
            parent::setArguments($arguments);

            return;
        }

        if ($arguments[static::$errorArgumentPositionOffset] < 0) {
            throw new UnexpectedValueException('Wrong error argument value or position offset');
        }

        $argument = $arguments[static::$errorArgumentPositionOffset];
        $argumentsBefore = array_slice($arguments, 0, static::$errorArgumentPositionOffset);
        $argumentsAfter = array_slice($arguments, static::$errorArgumentPositionOffset + 1);

        parent::setArguments(array_merge(
            $argumentsBefore,
            [self::$errorModifier],
            [$argument],
            $argumentsAfter
        ));
    }
}
