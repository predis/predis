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
 * @see http://redis.io/commands/get
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class GET extends RedisCommand
{
    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return 'GET';
    }
}
