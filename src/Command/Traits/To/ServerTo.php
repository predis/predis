<?php

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
