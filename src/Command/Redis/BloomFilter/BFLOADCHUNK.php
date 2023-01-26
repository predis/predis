<?php

namespace Predis\Command\Redis\BloomFilter;

use Predis\Command\Command as RedisCommand;

/**
 * @link https://redis.io/commands/bf.loadchunk/
 *
 * Restores a filter previously saved using SCANDUMP. See the SCANDUMP command for example usage.
 */
class BFLOADCHUNK extends RedisCommand
{
    public function getId()
    {
        return 'BF.LOADCHUNK';
    }
}
