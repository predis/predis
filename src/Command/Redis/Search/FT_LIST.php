<?php

namespace Predis\Command\Redis\Search;

use Predis\Command\Command as RedisCommand;

class FT_LIST extends RedisCommand
{
    /**
     * @return string
     */
    public function getId()
    {
        return 'FT._LIST';
    }
}
