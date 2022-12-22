<?php

namespace Predis\Command\Redis;

use Predis\Command\Command as RedisCommand;
use Predis\Command\Traits\By\GeoBy;
use Predis\Command\Traits\Count;
use Predis\Command\Traits\From\GeoFrom;
use Predis\Command\Traits\Sorting;
use Predis\Command\Traits\Storedist;

/**
 * @link https://redis.io/commands/geosearchstore/
 *
 * This command is like GEOSEARCH, but stores the result in destination key.
 */
class GEOSEARCHSTORE extends RedisCommand
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
    use Storedist {
        Storedist::setArguments as setStoreDist;
    }

    protected static $sortArgumentPositionOffset = 4;
    protected static $countArgumentPositionOffset = 5;
    protected static $storeDistArgumentPositionOffset = 7;

    public function getId()
    {
        return 'GEOSEARCHSTORE';
    }

    public function setArguments(array $arguments)
    {
        $this->setStoreDist($arguments);
        $arguments = $this->getArguments();

        $this->setCount($arguments, $arguments[6] ?? false);
        $arguments = $this->getArguments();

        $this->setSorting($arguments);
        $arguments = $this->getArguments();

        $this->setFrom($arguments);
        $arguments = $this->getArguments();

        $this->setBy($arguments);
        $this->filterArguments();
    }
}
