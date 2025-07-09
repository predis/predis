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

use Predis\Command\PrefixableCommand as RedisCommand;

/**
 * @see https://redis.io/commands/ft.aggregate/
 *
 * Run a search query on an index, and perform aggregate transformations
 * on the results, extracting statistics etc. from them
 */
class FTAGGREGATE extends RedisCommand
{
    public function getId()
    {
        return 'FT.AGGREGATE';
    }

    public function setArguments(array $arguments)
    {
        // If command already deserialized, bypass logic.
        if (in_array('DIALECT', $arguments)) {
            parent::setArguments($arguments);

            return;
        }

        [$index, $query] = $arguments;

        if (!empty($arguments[2]) && !in_array('DIALECT', $arguments[2]->toArray())) {
            // Default dialect is 2
            $arguments[2]->dialect(2);
        }

        $commandArguments = (!empty($arguments[2])) ? $arguments[2]->toArray() : ['DIALECT', 2];

        parent::setArguments(array_merge(
            [$index, $query],
            $commandArguments
        ));
    }

    public function prefixKeys($prefix)
    {
        $this->applyPrefixForFirstArgument($prefix);
    }
}
