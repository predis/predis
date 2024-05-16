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

namespace Predis\Command\Redis;

use Predis\Command\Argument\Hash\HGetFArguments;
use Predis\Command\Command as RedisCommand;

class HGETF extends RedisCommand
{
    public function getId()
    {
        return 'HGETF';
    }

    public function setArguments(array $arguments)
    {
        $processedArguments = [$arguments[0]];

        if (array_key_exists(2, $arguments) && $arguments[2] instanceof HGetFArguments) {
            $processedArguments = array_merge($processedArguments, $arguments[2]->toArray());
        }

        if (!empty($arguments[1])) {
            array_push($processedArguments, 'FIELDS', count($arguments[1]));
            $processedArguments = array_merge($processedArguments, $arguments[1]);
        }

        parent::setArguments($processedArguments);
    }
}
