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

namespace Predis\Command\Redis\BloomFilter;

use Predis\Command\Command as RedisCommand;

/**
 * @see https://redis.io/commands/bf.madd/
 *
 * Adds one or more items to the Bloom Filter and creates the filter if it does not exist yet.
 * This command operates identically to BF.ADD except that it allows multiple inputs and returns multiple values.
 */
class BFMADD extends RedisCommand
{
    public function getId()
    {
        return 'BF.MADD';
    }
}
