<?php

namespace Predis\Command\Redis\Json;

use Predis\Command\Command as RedisCommand;

/**
 * @link https://redis.io/commands/json.arrtrim/
 *
 * Trim an array so that it contains only the specified inclusive range of elements
 */
class JSONARRTRIM extends RedisCommand
{
    public function getId()
    {
        return 'JSON.ARRTRIM';
    }
}
