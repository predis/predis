<?php

namespace Predis\Command\Redis;

use Predis\Command\PrefixableCommand as RedisCommand;

class VRANGE extends RedisCommand
{
    public function getId()
    {
        return 'VRANGE';
    }

    public function prefixKeys($prefix)
    {
        $this->applyPrefixForFirstArgument($prefix);
    }
}
