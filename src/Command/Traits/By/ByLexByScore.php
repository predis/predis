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

namespace Predis\Command\Traits\By;

use Predis\Command\Command;
use UnexpectedValueException;

/**
 * @mixin Command
 */
trait ByLexByScore
{
    private static $argumentsEnum = [
        'bylex' => 'BYLEX',
        'byscore' => 'BYSCORE',
    ];

    public function setArguments(array $arguments)
    {
        if (count($arguments) <= static::$byLexByScoreArgumentPositionOffset || false === $arguments[static::$byLexByScoreArgumentPositionOffset]) {
            parent::setArguments($arguments);

            return;
        }

        $argument = $arguments[static::$byLexByScoreArgumentPositionOffset];

        if (is_string($argument) && in_array(strtoupper($argument), self::$argumentsEnum)) {
            $argument = self::$argumentsEnum[$argument];
        } else {
            throw new UnexpectedValueException('By argument accepts only "bylex" and "byscore" values');
        }

        $argumentsBefore = array_slice($arguments, 0, static::$byLexByScoreArgumentPositionOffset);
        $argumentsAfter = array_slice($arguments, static::$byLexByScoreArgumentPositionOffset + 1);

        parent::setArguments(array_merge($argumentsBefore, [$argument], $argumentsAfter));
    }
}
