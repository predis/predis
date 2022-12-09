<?php

namespace Predis\Command\Redis;

use Predis\Command\Traits\Keys;
use Predis\Command\Command as RedisCommand;

class BZPOPMIN extends RedisCommand
{
    use Keys {
        Keys::setArguments as setKeys;
    }

    protected static $keysArgumentPositionOffset = 0;

    public function getId()
    {
        return 'BZPOPMIN';
    }

    public function setArguments(array $arguments)
    {
        $this->setKeys($arguments, false);
    }

    public function parseResponse($data)
    {
        $key = array_shift($data);

        if (null === $key) {
            return [$key];
        }

        return array_combine([$key], [[$data[0] => $data[1]]]);
    }
}
