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
 * @link http://redis.io/commands/slaveof
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class SLAVEOF extends RedisCommand
{
    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return 'SLAVEOF';
    }

    /**
     * {@inheritdoc}
     */
    public function setArguments(array $arguments)
    {
        if (count($arguments) === 0 || $arguments[0] === 'NO ONE') {
            $arguments = array('NO', 'ONE');
        }

        parent::setArguments($arguments);
    }
}
