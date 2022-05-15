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

/**
 * @see http://redis.io/commands/zrevrange
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class ZREVRANGE extends ZRANGE
{
    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return 'ZREVRANGE';
    }
}
