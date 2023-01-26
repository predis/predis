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
