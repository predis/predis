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
 * @link http://redis.io/commands/sdiffstore
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class SDIFFSTORE extends RedisCommand
{
    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return 'SDIFFSTORE';
    }

    /**
     * {@inheritdoc}
     */
    public function setArguments(array $arguments)
    {
        if (count($arguments) === 2 && is_array($arguments[1])) {
            $arguments = array_merge(array($arguments[0]), $arguments[1]);
        }

        parent::setArguments($arguments);
    }
}
