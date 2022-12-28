<?php

namespace Predis\Command\Traits\From;

use InvalidArgumentException;
use Predis\Command\Argument\Geospatial\FromInterface;

trait GeoFrom
{
    public function setArguments(array $arguments)
    {
        $argumentPositionOffset = $this->getFromArgumentPositionOffset($arguments);

        if (null === $argumentPositionOffset) {
            throw new InvalidArgumentException('Invalid FROM argument value given');
        }

        $fromArgumentObject = $arguments[$argumentPositionOffset];
        $argumentsBefore = array_slice($arguments, 0, $argumentPositionOffset);
        $argumentsAfter = array_slice($arguments, $argumentPositionOffset + 1);

        parent::setArguments(array_merge(
            $argumentsBefore,
            $fromArgumentObject->toArray(),
            $argumentsAfter
        ));
    }

    private function getFromArgumentPositionOffset(array $arguments): ?int
    {
        foreach ($arguments as $i => $value) {
            if ($value instanceof FromInterface) {
                return $i;
            }
        }

        return null;
    }
}
