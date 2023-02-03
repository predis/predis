<?php

namespace Predis\Command\Redis\TDigest;

use Predis\Command\Command as RedisCommand;

/**
 * @see https://redis.io/commands/tdigest.add/
 *
 * Adds one or more observations to a t-digest sketch.
 */
class TDIGESTADD extends RedisCommand
{
    public function getId()
    {
        return 'TDIGEST.ADD';
    }
}
