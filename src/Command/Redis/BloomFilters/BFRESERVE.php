<?php

namespace Predis\Command\Redis\BloomFilters;

use Predis\Command\Command as RedisCommand;
use Predis\Command\Traits\BloomFilters\Expansion;

class BFRESERVE extends RedisCommand
{
    use Expansion {
        Expansion::setArguments as setExpansion;
    }

    protected static $expansionArgumentPositionOffset = 3;

    public function getId()
    {
        return 'BF.RESERVE';
    }

    public function setArguments(array $arguments)
    {
        if (array_key_exists(4, $arguments) && $arguments[4]) {
            $arguments[4] = 'NONSCALING';
        }

        $this->setExpansion($arguments);
        $this->filterArguments();
    }
}
