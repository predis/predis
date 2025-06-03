<?php

namespace Predis\Command\Redis;

use Predis\Command\Command as RedisCommand;

class VRANDMEMBER extends RedisCommand
{
    /**
     * @return string
     */
    public function getId(): string
    {
        return 'VRANDMEMBER';
    }

    /**
     * @param array $arguments
     * @return void
     */
    public function setArguments(array $arguments)
    {
        $lastArg = array_pop($arguments);

        if (!is_null($lastArg)) {
            $arguments[] = $lastArg;
        }

        parent::setArguments($arguments);
    }
}
