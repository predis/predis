<?php

/*
 * This file is part of the Predis package.
 *
 * (c) 2009-2020 Daniele Alessandri
 * (c) 2021-2025 Till Krüss
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

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
     * @param  array $arguments
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
