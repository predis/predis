<?php

namespace Predis\Command\Redis\TDigest;

use Predis\Command\Command as RedisCommand;

/**
 * @see https://redis.io/commands/tdigest.reset/
 *
 * Resets a t-digest sketch: empty the sketch and re-initializes it.
 */
class TDIGESTRESET extends RedisCommand
{
    public function getId()
    {
        return 'TDIGEST.RESET';
    }
}
