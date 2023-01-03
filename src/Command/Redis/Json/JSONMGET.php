<?php

namespace Predis\Command\Redis\Json;

use Predis\Command\Command as RedisCommand;

class JSONMGET extends RedisCommand
{
    public function getId()
    {
        return 'JSON.MGET';
    }

    public function setArguments(array $arguments)
    {
        $unpackedArguments = [];

        foreach ($arguments[0] as $key) {
            $unpackedArguments[] = $key;
        }

        $unpackedArguments[] = $arguments[1];

        parent::setArguments($unpackedArguments);
    }
}
