<?php

namespace Predis\Command\Redis\CountMinSketch;

use Predis\Command\Command as RedisCommand;

/**
 * @see https://redis.io/commands/cms.initbydim/
 *
 * Initializes a Count-Min Sketch to dimensions specified by user.
 */
class CMSINITBYDIM extends RedisCommand
{
    public function getId()
    {
        return 'CMS.INITBYDIM';
    }
}
