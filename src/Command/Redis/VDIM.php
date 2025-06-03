<?php

namespace Predis\Command\Redis;

use Predis\Command\Command as RedisCommand;

class VDIM extends RedisCommand
{
    /**
     * @return string
     */
    public function getId()
    {
        return 'VDIM';
    }
}
