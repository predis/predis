<?php

namespace Predis\Command\Redis;

use Predis\Command\Command as RedisCommand;
use Predis\Command\Redis\Utils\CommandUtility;

class VINFO extends RedisCommand
{
    /**
     * @return string
     */
    public function getId(): string
    {
        return 'VINFO';
    }

    /**
     * @param $data
     * @return array|null
     */
    public function parseResponse($data): ?array
    {
        if (!is_null($data)) {
            return CommandUtility::arrayToDictionary($data);
        }

        return $data;
    }
}
