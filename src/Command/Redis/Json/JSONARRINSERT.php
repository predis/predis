<?php

namespace Predis\Command\Redis\Json;

use Predis\Command\Command as RedisCommand;

/**
 * @link https://redis.io/commands/json.arrinsert/
 *
 * Insert the json values into the array at path before the index (shifts to the right)
 */
class JSONARRINSERT extends RedisCommand
{
    public function getId()
    {
        return 'JSON.ARRINSERT';
    }
}
