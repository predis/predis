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
 * @link http://redis.io/commands/zadd
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class ZADD extends RedisCommand
{
    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return 'ZADD';
    }

    /**
     * {@inheritdoc}
     */
    public function setArguments(array $arguments)
    {
        if (is_array(end($arguments))) {
            foreach (array_pop($arguments) as $member => $score) {
                $arguments[] = $score;
                $arguments[] = $member;
            }
        }

        parent::setArguments($arguments);
    }
}
