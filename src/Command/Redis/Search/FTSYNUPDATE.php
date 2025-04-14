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
 * @see https://redis.io/commands/ft.synupdate/
 *
 * Update a synonym group
 */
class FTSYNUPDATE extends RedisCommand
{
    public function getId()
    {
        return 'FT.SYNUPDATE';
    }

    public function setArguments(array $arguments)
    {
        [$index, $synonymGroupId] = $arguments;
        $commandArguments = [];

        if (!empty($arguments[2])) {
            $commandArguments = $arguments[2]->toArray();
        }

        $terms = array_slice($arguments, 3);

        parent::setArguments(array_merge(
            [$index, $synonymGroupId],
            $commandArguments,
            $terms
        ));
    }
}
