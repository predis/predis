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

class FTSPELLCHECK extends RedisCommand
{
    public function getId()
    {
        return 'FT.SPELLCHECK';
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

        $commandArguments = ['DIALECT', 2];

        if (!empty($arguments[2])) {
            $commandArguments = $arguments[2]->toArray();
        }

        parent::setArguments(array_merge(
            [$index, $query],
            $commandArguments
        ));
    }
}
