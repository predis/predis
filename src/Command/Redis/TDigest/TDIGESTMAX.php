<?php

namespace Predis\Command\Redis\TDigest;

use Predis\Command\Command as RedisCommand;

/**
 * @see https://redis.io/commands/tdigest.max/
 *
 * Returns the maximum observation value from a t-digest sketch.
 */
class TDIGESTMAX extends RedisCommand
{
    public function getId()
    {
        return 'TDIGEST.MAX';
    }
}
