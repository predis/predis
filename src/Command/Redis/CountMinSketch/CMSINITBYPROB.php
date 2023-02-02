<?php

namespace Predis\Command\Redis\CountMinSketch;

use Predis\Command\Command as RedisCommand;

/**
 * @see https://redis.io/commands/cms.initbyprob/
 *
 * Initializes a Count-Min Sketch to accommodate requested tolerances.
 */
class CMSINITBYPROB extends RedisCommand
{
    public function getId()
    {
        return 'CMS.INITBYPROB';
    }
}
