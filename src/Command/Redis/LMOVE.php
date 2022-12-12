<?php

namespace Predis\Command\Redis;

use Predis\Command\Command as RedisCommand;

class LMOVE extends RedisCommand
{
    public function getId()
    {
        return 'LMOVE';
    }
}
