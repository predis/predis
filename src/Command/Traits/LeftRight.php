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

use Predis\Command\Command;
use UnexpectedValueException;

/**
 * @mixin Command
 */
trait LeftRight
{
    /**
     * @var array{string: string}
     */
    private static $leftRightEnum = [
        'left' => 'LEFT',
        'right' => 'RIGHT',
    ];

    public function setArguments(array $arguments)
    {
        $argumentsLength = count($arguments);

        if (static::$leftRightArgumentPositionOffset >= $argumentsLength) {
            $arguments[] = 'LEFT';
            parent::setArguments($arguments);

            return;
        }

        $argument = $arguments[static::$leftRightArgumentPositionOffset];

        if (is_string($argument) && in_array(strtoupper($argument), self::$leftRightEnum, true)) {
            $argument = self::$leftRightEnum[$argument];
        } else {
            $enumValues = implode(', ', array_keys(self::$leftRightEnum));
            throw new UnexpectedValueException("Left/Right argument accepts only: {$enumValues} values");
        }

        $argumentsBefore = array_slice($arguments, 0, static::$leftRightArgumentPositionOffset);
        $argumentsAfter = array_slice($arguments, static::$leftRightArgumentPositionOffset + 1);

        parent::setArguments(array_merge(
            $argumentsBefore,
            [$argument],
            $argumentsAfter
        ));
    }
}
