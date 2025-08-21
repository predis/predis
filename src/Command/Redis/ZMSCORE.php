<?php

/*
 * This file is part of the Predis package.
 *
 * (c) 2009-2020 Daniele Alessandri
 * (c) 2021-2025 Till Krüss
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Command\Redis;

use Predis\Command\Command as RedisCommand;

/**
 * @see https://redis.io/commands/zmscore/
 *
 * Returns the scores associated with the specified members
 * in the sorted set stored at key.
 *
 * For every member that does not exist in the sorted set, a null value is returned.
 */
class ZMSCORE extends RedisCommand
{
    /**
     * {@inheritDoc}
     */
    public function getId()
    {
        return 'ZMSCORE';
    }
}
