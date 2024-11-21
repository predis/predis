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
