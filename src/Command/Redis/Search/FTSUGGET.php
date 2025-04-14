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

namespace Predis\Command\Redis\Search;

use Predis\Command\Command as RedisCommand;

/**
 * @see https://redis.io/commands/ft.sugget/
 *
 * Get completion suggestions for a prefix.
 */
class FTSUGGET extends RedisCommand
{
    public function getId()
    {
        return 'FT.SUGGET';
    }

    public function setArguments(array $arguments)
    {
        [$key, $prefix] = $arguments;
        $commandArguments = (!empty($arguments[2])) ? $arguments[2]->toArray() : [];

        parent::setArguments(array_merge(
            [$key, $prefix],
            $commandArguments
        ));
    }
}
