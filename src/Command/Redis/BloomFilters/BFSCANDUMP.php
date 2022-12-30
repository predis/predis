<?php

namespace Predis\Command\Redis\BloomFilters;

use Predis\Command\Command as RedisCommand;

/**
 * @link https://redis.io/commands/bf.scandump/
 *
 * Begins an incremental save of the bloom filter.
 * This is useful for large bloom filters which cannot fit into the normal DUMP and RESTORE model.
 */
class BFSCANDUMP extends RedisCommand
{
    public function getId()
    {
        return 'BF.SCANDUMP';
    }
}
