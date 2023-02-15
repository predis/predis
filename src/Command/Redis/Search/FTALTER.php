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

use Predis\Command\Argument\Search\CreateArguments;
use Predis\Command\Command as RedisCommand;

class FTALTER extends RedisCommand
{
    public function getId()
    {
        return 'FT.ALTER';
    }

    public function setArguments(array $arguments)
    {
        [$index, $schema] = $arguments;
        $commandArguments = [];

        if (!empty($arguments[2])) {
            $commandArguments = (new CreateArguments())->skipInitialScan()->toArray();
        }

        parent::setArguments(array_merge(
            [$index],
            $commandArguments,
            $schema->toArray()
        ));
    }
}
