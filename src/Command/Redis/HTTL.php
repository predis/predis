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

namespace Predis\Command\Redis;

use Predis\Command\Command as RedisCommand;

class HTTL extends RedisCommand
{
    public function getId()
    {
        return 'HTTL';
    }

    public function setArguments(array $arguments)
    {
        $processedArguments = [$arguments[0], 'FIELDS', count($arguments[1])];
        $processedArguments = array_merge($processedArguments, $arguments[1]);

        parent::setArguments($processedArguments);
    }
}
