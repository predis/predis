<?php

namespace Predis\Command\Redis\BloomFilters;

use Predis\Command\Command as RedisCommand;

class BFCARD extends RedisCommand
{
    public function getId()
    {
        return 'BF.CARD';
    }
}
