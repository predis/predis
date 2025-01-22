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
 * @see  https://redis.io/commands/ft.search/
 *
 * Search the index with a textual query, returning either documents or just ids
 */
class FTSEARCH extends RedisCommand
{
    public function getId()
    {
        return 'FT.SEARCH';
    }

    public function setArguments(array $arguments)
    {
        [$index, $query] = $arguments;
        $commandArguments = (!empty($arguments[2])) ? $arguments[2]->toArray() : [];

        parent::setArguments(array_merge(
            [$index, $query],
            $commandArguments
        ));
    }
}
