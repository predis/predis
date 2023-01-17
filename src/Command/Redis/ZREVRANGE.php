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

/**
 * @see http://redis.io/commands/zrevrange
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
