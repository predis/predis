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
 * @see http://redis.io/commands/hmset
 */
class HMSET extends RedisCommand
{
    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return 'HMSET';
    }

    /**
     * {@inheritdoc}
     */
    public function setArguments(array $arguments)
    {
        if (count($arguments) === 2 && is_array($arguments[1])) {
            $flattenedKVs = [$arguments[0]];
            $args = $arguments[1];

            foreach ($args as $k => $v) {
                $flattenedKVs[] = $k;
                $flattenedKVs[] = $v;
            }

            $arguments = $flattenedKVs;
        }

        parent::setArguments($arguments);
    }
}
