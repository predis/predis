<?php

namespace Predis\Command\Redis\CountMinSketch;

use Predis\Command\Command as RedisCommand;

/**
 * @see https://redis.io/commands/cms.query/
 *
 * Returns the count for one or more items in a sketch.
 */
class CMSQUERY extends RedisCommand
{
    public function getId()
    {
        return 'CMS.QUERY';
    }
}
