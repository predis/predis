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

namespace Predis\Command\Traits\To;

use Predis\Command\Argument\Server\To;

trait ServerTo
{
    public function setArguments(array $arguments)
    {
        $argumentsLength = count($arguments);

        if (static::$toArgumentPositionOffset >= $argumentsLength) {
            parent::setArguments($arguments);

            return;
        }

        /** @var To|null $toArgument */
        $toArgument = $arguments[static::$toArgumentPositionOffset];

        if (null === $toArgument) {
            array_splice($arguments, static::$toArgumentPositionOffset, 1, [false]);
            parent::setArguments($arguments);

            return;
        }

        $argumentsBefore = array_slice($arguments, 0, static::$toArgumentPositionOffset);
        $argumentsAfter = array_slice($arguments, static::$toArgumentPositionOffset + 1);

        parent::setArguments(array_merge(
            $argumentsBefore,
            $toArgument->toArray(),
            $argumentsAfter
        ));
    }
}
