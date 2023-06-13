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

namespace Predis\Command\Redis\Json;

use Predis\Command\Command as RedisCommand;

/**
 * @see https://redis.io/commands/json.merge/
 *
 * Merge a given JSON value into matching paths.
 * Consequently, JSON values at matching paths are updated, deleted, or expanded with new children.
 */
class JSONMERGE extends RedisCommand
{
    public function getId()
    {
        return 'JSON.MERGE';
    }
}
