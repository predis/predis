<?php

namespace Predis\Command\Redis;

use Predis\Command\Command as RedisCommand;

class VCARD extends RedisCommand
{
    /**
     * @return string
     */
    public function getId()
    {
        return 'VCARD';
    }
}
