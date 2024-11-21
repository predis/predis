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
trait Count
{
    private $countModifier = 'COUNT';
    private $anyModifier = 'ANY';

    public function setArguments(array $arguments, bool $any = false)
    {
        $argumentsLength = count($arguments);

        if (static::$countArgumentPositionOffset >= $argumentsLength) {
            parent::setArguments($arguments);

            return;
        }

        if ($arguments[static::$countArgumentPositionOffset] === -1) {
            array_splice($arguments, static::$countArgumentPositionOffset, 1, [false]);
            parent::setArguments($arguments);

            return;
        }

        if ($arguments[static::$countArgumentPositionOffset] < 1) {
            throw new UnexpectedValueException('Wrong count argument value or position offset');
        }

        $countArgument = $arguments[static::$countArgumentPositionOffset];
        $argumentsBefore = array_slice($arguments, 0, static::$countArgumentPositionOffset);
        $argumentsAfter = array_slice($arguments, static::$countArgumentPositionOffset + 2);

        if (!$any) {
            $argumentsAfter = array_slice($arguments, static::$countArgumentPositionOffset + 1);
            parent::setArguments(array_merge(
                $argumentsBefore,
                [$this->countModifier],
                [$countArgument],
                $argumentsAfter
            ));

            return;
        }

        parent::setArguments(array_merge(
            $argumentsBefore,
            [$this->countModifier],
            [$countArgument],
            [$this->anyModifier],
            $argumentsAfter
        ));
    }
}
