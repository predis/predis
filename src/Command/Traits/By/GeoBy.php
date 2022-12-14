<?php

namespace Predis\Command\Traits\By;

use Predis\Command\Argument\Geospatial\ByInterface;
use UnexpectedValueException;

class GeoBy
{
    public function setArguments(array $arguments)
    {
        $argumentsLength = count($arguments);

        if (static::$byArgumentPositionOffset >= $argumentsLength) {
            parent::setArguments($arguments);
            return;
        }

        if (!$arguments[static::$byArgumentPositionOffset] instanceof ByInterface) {
            throw new UnexpectedValueException('Wrong BY argument type given');
        }

        $byArgumentObject = $arguments[static::$byArgumentPositionOffset];
        $argumentsBefore = array_slice($arguments, 0, static::$byArgumentPositionOffset);
        $argumentsAfter = array_slice($arguments, static::$byArgumentPositionOffset + 1);

        parent::setArguments(array_merge(
            $argumentsBefore,
            $byArgumentObject->toArray(),
            $argumentsAfter
        ));
    }
}
