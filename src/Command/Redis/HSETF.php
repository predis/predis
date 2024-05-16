<?php

/*
 * This file is part of the Predis package.
 *
 * (c) 2009-2020 Daniele Alessandri
 * (c) 2021-2023 Till Krüss
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Command\Redis;

use Predis\Command\Argument\Hash\HSetFArguments;
use Predis\Command\Command as RedisCommand;

class HSETF extends RedisCommand
{
    public function getId()
    {
        return 'HSETF';
    }

    public function setArguments(array $arguments)
    {
        $processedArguments = [$arguments[0]];

        if (array_key_exists(2, $arguments) && $arguments[2] instanceof HSetFArguments) {
            $processedArguments = array_merge($processedArguments, $arguments[2]->toArray());
        }

        if (!empty($arguments[1])) {
            $processedArguments[] = 'FVS';
            $keys = array_keys($arguments[1]);
            $keyValueArray = [];

            foreach ($keys as $key) {
                array_push($keyValueArray, $key, $arguments[1][$key]);
            }

            $processedArguments[] = count($keyValueArray) / 2;
            $processedArguments = array_merge($processedArguments, $keyValueArray);
        }

        parent::setArguments($processedArguments);
    }
}
