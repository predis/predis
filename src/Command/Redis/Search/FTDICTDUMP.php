<?php

namespace Predis\Command\Redis\Search;

use Predis\Command\Command as RedisCommand;

/**
 * @see https://redis.io/commands/ft.dictdump/
 *
 * Dump all terms in the given dictionary.
 */
class FTDICTDUMP extends RedisCommand
{
    public function getId()
    {
        return 'FT.DICTDUMP';
    }
}
