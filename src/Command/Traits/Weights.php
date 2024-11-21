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
trait Weights
{
    /**
     * @var string
     */
    private static $weightsModifier = 'WEIGHTS';

    public function setArguments(array $arguments)
    {
        $argumentsLength = count($arguments);

        if (static::$weightsArgumentPositionOffset >= $argumentsLength) {
            parent::setArguments($arguments);

            return;
        }

        if (!is_array($arguments[static::$weightsArgumentPositionOffset])) {
            throw new UnexpectedValueException('Wrong weights argument type');
        }

        $weightsArray = $arguments[static::$weightsArgumentPositionOffset];

        if (empty($weightsArray)) {
            unset($arguments[static::$weightsArgumentPositionOffset]);
            parent::setArguments($arguments);

            return;
        }

        $argumentsBefore = array_slice($arguments, 0, static::$weightsArgumentPositionOffset);
        $argumentsAfter = array_slice($arguments, static::$weightsArgumentPositionOffset + 1);

        parent::setArguments(array_merge(
            $argumentsBefore,
            [self::$weightsModifier],
            $weightsArray,
            $argumentsAfter
        ));
    }
}
