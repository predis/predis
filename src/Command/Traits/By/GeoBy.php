<?php

namespace Predis\Command\Traits\By;

use InvalidArgumentException;
use Predis\Command\Argument\Geospatial\ByInterface;

trait GeoBy
{
    public function setArguments(array $arguments)
    {
        $argumentPositionOffset = $this->getByArgumentPositionOffset($arguments);

        if (null === $argumentPositionOffset) {
            throw new InvalidArgumentException('Invalid BY argument value given');
        }

        $byArgumentObject = $arguments[$argumentPositionOffset];
        $argumentsBefore = array_slice($arguments, 0, $argumentPositionOffset);
        $argumentsAfter = array_slice($arguments, $argumentPositionOffset + 1);

        parent::setArguments(array_merge(
            $argumentsBefore,
            $byArgumentObject->toArray(),
            $argumentsAfter
        ));
    }

    private function getByArgumentPositionOffset(array $arguments): ?int
    {
        foreach ($arguments as $i => $value) {
            if ($value instanceof ByInterface) {
                return $i;
            }
        }

        return null;
    }
}
