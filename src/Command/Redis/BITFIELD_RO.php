<?php

namespace Predis\Command\Redis;

use Predis\Command\Command as RedisCommand;

class BITFIELD_RO extends RedisCommand
{
    /**
     * @return string
     */
    public function getId()
    {
        return 'BITFIELD_RO';
    }

    /**
     * @param array $arguments
     * @return void
     */
    public function setArguments(array $arguments)
    {
        $processedArguments = [$arguments[0]];

        if (array_key_exists(1, $arguments) && is_array($arguments[1])) {
            // Convert encoding => offset, into GET, encoding, offset
            array_walk($arguments[1], function ($value, $key) use (&$processedArguments) {
                array_push($processedArguments, 'GET', $key, $value);
            });
        }

        parent::setArguments($processedArguments);
    }
}
