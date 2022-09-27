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
 * @link http://redis.io/commands/xrange
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class XRANGE extends RedisCommand
{
    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return 'XRANGE';
    }

    /**
     * {@inheritdoc}
     */
    public function setArguments(array $arguments)
    {
        if (count($arguments) === 4) {
            $arguments[] = $arguments[3];
            $arguments[3] = 'COUNT';
        }

        parent::setArguments($arguments);
    }

    /**
     * {@inheritdoc}
     */
    public function parseResponse($data)
    {
        $result = array();
        foreach ($data as $entry) {
            $processed = array();
            $count = count($entry[1]);

            for ($i = 0; $i < $count; ++$i) {
                $processed[$entry[1][$i]] = $entry[1][++$i];
            }

            $result[$entry[0]] = $processed;
        }

        return $result;
    }
}
