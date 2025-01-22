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

/**
 * @see http://redis.io/commands/blpop
 */
class BLPOP extends RedisCommand
{
    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return 'BLPOP';
    }

    /**
     * {@inheritdoc}
     */
    public function setArguments(array $arguments)
    {
        if (count($arguments) === 2 && is_array($arguments[0])) {
            [$arguments, $timeout] = $arguments;
            array_push($arguments, $timeout);
        }

        parent::setArguments($arguments);
    }
}
