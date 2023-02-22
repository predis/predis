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

namespace Predis\Command\Redis\Search;

use Predis\Command\Command as RedisCommand;

/**
 * @see https://redis.io/commands/ft.create/
 *
 * Create an index with the given specification
 */
class FTCREATE extends RedisCommand
{
    public function getId()
    {
        return 'FT.CREATE';
    }

    public function setArguments(array $arguments)
    {
        [$index, $schema] = $arguments;
        $commandArguments = (!empty($arguments[2])) ? $arguments[2]->toArray() : [];

        parent::setArguments(array_merge(
            [$index],
            $commandArguments,
            $schema->toArray()
        ));
    }
}
