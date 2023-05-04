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

namespace Predis\Command\Redis\CuckooFilter;

use Predis\Command\Command as RedisCommand;
use Predis\Command\Traits\BloomFilters\Capacity;
use Predis\Command\Traits\BloomFilters\Items;
use Predis\Command\Traits\BloomFilters\NoCreate;

class CFINSERT extends RedisCommand
{
    use Capacity {
        Capacity::setArguments as setCapacity;
    }
    use NoCreate {
        NoCreate::setArguments as setNoCreate;
    }
    use Items {
        Items::setArguments as setItems;
    }

    protected static $capacityArgumentPositionOffset = 1;
    protected static $noCreateArgumentPositionOffset = 2;
    protected static $itemsArgumentPositionOffset = 3;

    public function getId()
    {
        return 'CF.INSERT';
    }

    public function setArguments(array $arguments)
    {
        $this->setNoCreate($arguments);
        $arguments = $this->getArguments();

        $this->setItems($arguments);
        $arguments = $this->getArguments();

        $this->setCapacity($arguments);
        $this->filterArguments();
    }
}
