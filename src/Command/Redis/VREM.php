<?php

namespace Predis\Command\Redis;

use Predis\Command\Command as RedisCommand;

class VREM extends RedisCommand
{
    /**
     * @return string
     */
    public function getId(): string
    {
        return 'VREM';
    }

    /**
     * @param $data
     * @return bool
     */
    public function parseResponse($data): bool
    {
        return (bool) $data;
    }
}
