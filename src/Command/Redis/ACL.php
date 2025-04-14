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

/**
 * @see https://redis.io/commands/?name=ACL
 *
 * Container command corresponds to any ACL *.
 * Represents any ACL command with subcommand as first argument.
 */
class ACL extends RedisCommand
{
    public function getId()
    {
        return 'ACL';
    }

    /**
     * {@inheritdoc}
     */
    public function parseResponse($data)
    {
        if (!is_array($data)) {
            return $data;
        }

        if ($data === array_values($data)) {
            return $data;
        }

        // flatten Relay (RESP3) maps
        $return = [];

        array_walk($data, function ($value, $key) use (&$return) {
            $return[] = $key;
            $return[] = $value;
        });

        return $return;
    }
}
