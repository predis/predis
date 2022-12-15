<?php

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
