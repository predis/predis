<?php

namespace Predis\Command\Redis\BloomFilters;

use Predis\Command\Command as RedisCommand;

/**
 * @link https://redis.io/commands/bf.madd/
 *
 * Adds one or more items to the Bloom Filter and creates the filter if it does not exist yet.
 * This command operates identically to BF.ADD except that it allows multiple inputs and returns multiple values.
 */
class BFMADD extends RedisCommand
{
    public function getId()
    {
        return 'BF.MADD';
    }
}
