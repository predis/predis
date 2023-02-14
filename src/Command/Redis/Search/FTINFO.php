<?php

namespace Predis\Command\Redis\Search;

use Predis\Command\Command as RedisCommand;

/**
 * @see https://redis.io/commands/ft.info/
 *
 * Return information and statistics on the index.
 */
class FTINFO extends RedisCommand
{
    public function getId()
    {
        return 'FT.INFO';
    }
}
