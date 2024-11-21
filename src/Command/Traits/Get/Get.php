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

namespace Predis\Command\Traits\Get;

use UnexpectedValueException;

trait Get
{
    private static $getModifier = 'GET';

    public function setArguments(array $arguments)
    {
        $argumentsLength = count($arguments);

        if (static::$getArgumentPositionOffset >= $argumentsLength) {
            parent::setArguments($arguments);

            return;
        }

        if (!is_array($arguments[static::$getArgumentPositionOffset])) {
            throw new UnexpectedValueException('Wrong get argument type');
        }

        $patterns = [];

        foreach ($arguments[static::$getArgumentPositionOffset] as $pattern) {
            $patterns[] = self::$getModifier;
            $patterns[] = $pattern;
        }

        $argumentsBeforeKeys = array_slice($arguments, 0, static::$getArgumentPositionOffset);
        $argumentsAfterKeys = array_slice($arguments, static::$getArgumentPositionOffset + 1);

        parent::setArguments(array_merge($argumentsBeforeKeys, $patterns, $argumentsAfterKeys));
    }
}
