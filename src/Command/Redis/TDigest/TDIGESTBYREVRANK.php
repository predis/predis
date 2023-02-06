<?php

namespace Predis\Command\Redis\TDigest;

use Predis\Command\Command as RedisCommand;

/**
 * @see https://redis.io/commands/tdigest.byrevrank/
 *
 * Returns, for each input reverse rank, an estimation of the value (floating-point) with that reverse rank.
 */
class TDIGESTBYREVRANK extends RedisCommand
{
    public function getId()
    {
        return 'TDIGEST.BYREVRANK';
    }
}
