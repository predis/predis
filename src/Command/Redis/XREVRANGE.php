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
 * @link http://redis.io/commands/xrevrange
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class XREVRANGE extends XRANGE
{
    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return 'XREVRANGE';
    }
}
