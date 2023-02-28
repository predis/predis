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
 * @see https://redis.io/commands/ft.sugadd/
 *
 * Add a suggestion string to an auto-complete suggestion dictionary.
 */
class FTSUGADD extends RedisCommand
{
    public function getId()
    {
        return 'FT.SUGADD';
    }

    public function setArguments(array $arguments)
    {
        [$key, $string, $score] = $arguments;
        $commandArguments = (!empty($arguments[3])) ? $arguments[3]->toArray() : [];

        parent::setArguments(array_merge(
            [$key, $string, $score],
            $commandArguments
        ));
    }
}
