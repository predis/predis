<?php

namespace Predis\Command\Redis\BloomFilters;

use Predis\Command\Command as RedisCommand;
use Predis\Command\Traits\BloomFilters\Capacity;
use Predis\Command\Traits\BloomFilters\Error;
use Predis\Command\Traits\BloomFilters\Expansion;
use Predis\Command\Traits\BloomFilters\Items;

class BFINSERT extends RedisCommand
{
    use Capacity {
        Capacity::setArguments as setCapacity;
    }
    use Error {
        Error::setArguments as setErrorRate;
    }
    use Expansion {
        Expansion::setArguments as setExpansion;
    }
    use Items {
        Items::setArguments as setItems;
    }

    protected static $capacityArgumentPositionOffset = 1;
    protected static $errorArgumentPositionOffset = 2;
    protected static $expansionArgumentPositionOffset = 3;
    protected static $itemsArgumentPositionOffset = 6;

    public function getId()
    {
        return 'BF.INSERT';
    }

    public function setArguments(array $arguments)
    {
        if (array_key_exists(4, $arguments) && $arguments[4]) {
            $arguments[4] = 'NOCREATE';
        }

        if (array_key_exists(5, $arguments) && $arguments[5]) {
            $arguments[5] = 'NONSCALING';
        }

        $this->setItems($arguments);
        $arguments = $this->getArguments();

        $this->setExpansion($arguments);
        $arguments = $this->getArguments();

        $this->setErrorRate($arguments);
        $arguments = $this->getArguments();

        $this->setCapacity($arguments);
        $this->filterArguments();
    }
}
