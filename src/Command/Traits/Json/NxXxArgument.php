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

use Predis\Command\Command;
use UnexpectedValueException;

/**
 * @mixin Command
 */
trait NxXxArgument
{
    /**
     * @var string[]
     */
    private static $argumentEnum = [
        'nx' => 'NX',
        'xx' => 'XX',
    ];

    public function setArguments(array $arguments)
    {
        $argumentsLength = count($arguments);

        if (static::$nxXxArgumentPositionOffset >= $argumentsLength) {
            parent::setArguments($arguments);

            return;
        }

        if (null === $arguments[static::$nxXxArgumentPositionOffset]) {
            array_splice($arguments, static::$nxXxArgumentPositionOffset, 1, [false]);
            parent::setArguments($arguments);

            return;
        }

        $argument = $arguments[static::$nxXxArgumentPositionOffset];

        if (!in_array(strtoupper($argument), self::$argumentEnum, true)) {
            $enumValues = implode(', ', array_keys(self::$argumentEnum));
            throw new UnexpectedValueException("Argument accepts only: {$enumValues} values");
        }

        $argumentsBefore = array_slice($arguments, 0, static::$nxXxArgumentPositionOffset);
        $argumentsAfter = array_slice($arguments, static::$nxXxArgumentPositionOffset + 1);

        parent::setArguments(array_merge(
            $argumentsBefore,
            [self::$argumentEnum[strtolower($argument)]],
            $argumentsAfter
        ));
    }
}
