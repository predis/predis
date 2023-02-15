<?php

namespace Predis\Command\Redis\Search;

use Predis\Command\Command as RedisCommand;

/**
 * @see https://redis.io/commands/ft.aliasdel/
 *
 * Remove an alias from an index.
 */
class FTALIASDEL extends RedisCommand
{
    public function getId()
    {
        return 'FT.ALIASDEL';
    }
}
