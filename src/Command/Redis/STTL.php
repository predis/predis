<?php

/*
 * This file is part of the Predis package.
 *
 * (c) 2009-2020 Daniele Alessandri
 * (c) 2021-2026 Till Krüss
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Command\Redis;

use Predis\Command\PrefixableCommand as RedisCommand;

/**
 * @see https://redis.io/commands/sttl/
 */
class STTL extends RedisCommand
{
    public function getId()
    {
        return 'STTL';
    }

    public function setArguments(array $arguments)
    {
        $processedArguments = [$arguments[0]];

        if (!empty($arguments[1])) {
            array_push($processedArguments, 'MEMBERS', count($arguments[1]));
            $processedArguments = array_merge($processedArguments, $arguments[1]);
        }

        parent::setArguments($processedArguments);
    }

    public function prefixKeys($prefix)
    {
        $this->applyPrefixForFirstArgument($prefix);
    }
}
