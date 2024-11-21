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

/**
 * @mixin Command
 */
trait Items
{
    private static $itemsModifier = 'ITEMS';

    public function setArguments(array $arguments)
    {
        $argumentsLength = count($arguments);

        if (static::$itemsArgumentPositionOffset >= $argumentsLength) {
            parent::setArguments($arguments);

            return;
        }

        $argument = $arguments[static::$itemsArgumentPositionOffset];
        $argumentsBefore = array_slice($arguments, 0, static::$itemsArgumentPositionOffset);
        $argumentsAfter = array_slice($arguments, static::$itemsArgumentPositionOffset + 1);

        parent::setArguments(array_merge(
            $argumentsBefore,
            [self::$itemsModifier],
            [$argument],
            $argumentsAfter
        ));
    }
}
