<?php

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
