<?php

namespace Predis\Command\Traits\From;

use Predis\Command\Argument\Geospatial\FromInterface;
use UnexpectedValueException;

trait GeoFrom
{
    public function setArguments(array $arguments)
    {
        $argumentsLength = count($arguments);

        if (static::$fromArgumentPositionOffset >= $argumentsLength) {
            parent::setArguments($arguments);
            return;
        }

        if (!$arguments[static::$fromArgumentPositionOffset] instanceof FromInterface) {
            throw new UnexpectedValueException('Wrong FROM argument type given');
        }

        $fromArgumentObject = $arguments[static::$fromArgumentPositionOffset];
        $argumentsBefore = array_slice($arguments, 0, static::$fromArgumentPositionOffset);
        $argumentsAfter = array_slice($arguments, static::$fromArgumentPositionOffset + 1);

        parent::setArguments(array_merge(
            $argumentsBefore,
            $fromArgumentObject->toArray(),
            $argumentsAfter
        ));
    }
}
