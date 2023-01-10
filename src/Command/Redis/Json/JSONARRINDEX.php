<?php

namespace Predis\Command\Redis\Json;

use Predis\Command\Command as RedisCommand;

/**
 * @link https://redis.io/commands/json.arrindex/
 *
 * Search for the first occurrence of a JSON value in an array
 */
class JSONARRINDEX extends RedisCommand
{
    public function getId()
    {
        return 'JSON.ARRINDEX';
    }
}
