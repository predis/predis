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

use Predis\Command\Command as RedisCommand;

/**
 * Invokes given function from loaded Redis Gears library.
 *
 * In order to be used in cluster mode
 * @see https://github.com/predis/predis#redis-gears-with-cluster
 */
class TFCALL extends RedisCommand
{
    public function getId()
    {
        return 'TFCALL';
    }

    /**
     * @param  array $arguments
     * @return void
     */
    public function setArguments(array $arguments)
    {
        $keysCount = (array_key_exists(2, $arguments)) ? count($arguments[2]) : 0;
        $processedArguments = [$arguments[0] . '.' . $arguments[1], $keysCount];

        if (array_key_exists(2, $arguments)) {
            $processedArguments = array_merge($processedArguments, $arguments[2]);
        }

        if (array_key_exists(3, $arguments) && !empty($arguments[3])) {
            $processedArguments = array_merge($processedArguments, $arguments[3]);
        }

        parent::setArguments($processedArguments);
    }
}
