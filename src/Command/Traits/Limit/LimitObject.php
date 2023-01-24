<?php

namespace Predis\Command\Traits\Limit;

use Predis\Command\Argument\Server\LimitInterface;

trait LimitObject
{
    public function setArguments(array $arguments)
    {
        $argumentPositionOffset = $this->getLimitArgumentPositionOffset($arguments);

        if (null === $argumentPositionOffset) {
            parent::setArguments($arguments);
            return;
        }

        $limitObject = $arguments[$argumentPositionOffset];
        $argumentsBefore = array_slice($arguments, 0, $argumentPositionOffset);
        $argumentsAfter = array_slice($arguments, $argumentPositionOffset + 1);

        parent::setArguments(array_merge(
            $argumentsBefore,
            $limitObject->toArray(),
            $argumentsAfter
        ));
    }

    private function getLimitArgumentPositionOffset(array $arguments): ?int
    {
        foreach ($arguments as $i => $value) {
            if ($value instanceof LimitInterface) {
                return $i;
            }
        }

        return null;
    }
}
