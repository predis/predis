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
