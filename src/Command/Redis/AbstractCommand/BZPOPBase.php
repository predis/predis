<?php

namespace Predis\Command\Redis\AbstractCommand;
use Predis\Command\Command as RedisCommand;
use Predis\Command\Traits\Keys;

abstract class BZPOPBase extends RedisCommand
{
    use Keys {
        Keys::setArguments as setKeys;
    }

    protected static $keysArgumentPositionOffset = 0;

    abstract public function getId(): string;

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
