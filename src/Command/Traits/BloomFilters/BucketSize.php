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
trait BucketSize
{
    private static $bucketSizeModifier = 'BUCKETSIZE';

    public function setArguments(array $arguments)
    {
        $argumentsLength = count($arguments);

        if (static::$bucketSizeArgumentPositionOffset >= $argumentsLength) {
            parent::setArguments($arguments);

            return;
        }

        if ($arguments[static::$bucketSizeArgumentPositionOffset] === -1) {
            array_splice($arguments, static::$bucketSizeArgumentPositionOffset, 1, [false]);
            parent::setArguments($arguments);

            return;
        }

        if ($arguments[static::$bucketSizeArgumentPositionOffset] < 1) {
            throw new UnexpectedValueException('Wrong bucket size argument value or position offset');
        }

        $argument = $arguments[static::$bucketSizeArgumentPositionOffset];
        $argumentsBefore = array_slice($arguments, 0, static::$bucketSizeArgumentPositionOffset);
        $argumentsAfter = array_slice($arguments, static::$bucketSizeArgumentPositionOffset + 1);

        parent::setArguments(array_merge(
            $argumentsBefore,
            [self::$bucketSizeModifier],
            [$argument],
            $argumentsAfter
        ));
    }
}
