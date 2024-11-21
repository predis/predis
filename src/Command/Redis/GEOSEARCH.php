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

namespace Predis\Command\Redis;

use Predis\Command\Command as RedisCommand;
use Predis\Command\Traits\By\GeoBy;
use Predis\Command\Traits\Count;
use Predis\Command\Traits\From\GeoFrom;
use Predis\Command\Traits\Sorting;
use Predis\Command\Traits\With\WithCoord;
use Predis\Command\Traits\With\WithDist;
use Predis\Command\Traits\With\WithHash;

/**
 * @see https://redis.io/commands/geosearch/
 *
 * Return the members of a sorted set populated with geospatial information using GEOADD,
 * which are within the borders of the area specified by a given shape.
 *
 * This command extends the GEORADIUS command, so in addition to searching
 * within circular areas, it supports searching within rectangular areas.
 */
class GEOSEARCH extends RedisCommand
{
    use GeoFrom {
        GeoFrom::setArguments as setFrom;
    }
    use GeoBy {
        GeoBy::setArguments as setBy;
    }
    use Sorting {
        Sorting::setArguments as setSorting;
    }
    use Count {
        Count::setArguments as setCount;
    }
    use WithCoord {
        WithCoord::setArguments as setWithCoord;
    }
    use WithDist {
        WithDist::setArguments as setWithDist;
    }
    use WithHash {
        WithHash::setArguments as setWithHash;
    }

    protected static $sortArgumentPositionOffset = 3;
    protected static $countArgumentPositionOffset = 4;
    protected static $withCoordArgumentPositionOffset = 6;
    protected static $withDistArgumentPositionOffset = 7;
    protected static $withHashArgumentPositionOffset = 8;

    public function getId()
    {
        return 'GEOSEARCH';
    }

    public function setArguments(array $arguments)
    {
        $this->setSorting($arguments);
        $arguments = $this->getArguments();

        $this->setWithCoord($arguments);
        $arguments = $this->getArguments();

        $this->setWithDist($arguments);
        $arguments = $this->getArguments();

        $this->setWithHash($arguments);
        $arguments = $this->getArguments();

        $this->setCount($arguments, $arguments[5] ?? false);
        $arguments = $this->getArguments();

        $this->setFrom($arguments);
        $arguments = $this->getArguments();

        $this->setBy($arguments);
        $this->filterArguments();
    }

    public function parseResponse($data)
    {
        $parsedData = [];
        $itemKey = '';

        foreach ($data as $item) {
            if (!is_array($item)) {
                $parsedData[] = $item;
                continue;
            }

            foreach ($item as $key => $itemRow) {
                if ($key === 0) {
                    $itemKey = $itemRow;
                    continue;
                }

                if (is_string($itemRow)) {
                    $parsedData[$itemKey]['dist'] = round((float) $itemRow, 5);
                } elseif (is_int($itemRow)) {
                    $parsedData[$itemKey]['hash'] = $itemRow;
                } else {
                    $parsedData[$itemKey]['lng'] = round($itemRow[0], 5);
                    $parsedData[$itemKey]['lat'] = round($itemRow[1], 5);
                }
            }
        }

        return $parsedData;
    }
}
