<?php

/*
 * This file is part of the Predis package.
 *
 * (c) Daniele Alessandri <suppakilla@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Command\Redis;

use Predis\Command\Command as RedisCommand;

/**
 * @see http://redis.io/commands/config-set
 * @see http://redis.io/commands/config-get
 * @see http://redis.io/commands/config-resetstat
 * @see http://redis.io/commands/config-rewrite
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class CONFIG extends RedisCommand
{
    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return 'CONFIG';
    }

    /**
     * {@inheritdoc}
     */
    public function parseResponse($data)
    {
        if (is_array($data)) {
            $result = array();

            for ($i = 0; $i < count($data); ++$i) {
                $result[$data[$i]] = $data[++$i];
            }

            return $result;
        }

        return $data;
    }
}
