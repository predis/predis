<?php

namespace Predis\Command\Redis;

use Predis\Command\Command as RedisCommand;

class VSETATTR extends RedisCommand
{
    /**
     * @return string
     */
    public function getId(): string
    {
        return 'VSETATTR';
    }

    /**
     * @param array $arguments
     * @return void
     */
    public function setArguments(array $arguments)
    {
        $lastArg = array_pop($arguments);

        if (is_array($lastArg)) {
            $arguments[] = json_encode($lastArg);
        } else {
            $arguments[] = $lastArg;
        }

        parent::setArguments($arguments);
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
