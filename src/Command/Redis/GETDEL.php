<?php

namespace Predis\Command\Redis;

use Predis\Command\Command as RedisCommand;

class GETDEL extends RedisCommand
{
    public function getId()
    {
        return 'GETDEL';
    }
}
