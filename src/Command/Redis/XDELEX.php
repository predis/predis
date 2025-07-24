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

use Predis\Command\PrefixableCommand as RedisCommand;

/**
 * @see http://redis.io/commands/xdelex
 */
class XDELEX extends RedisCommand
{
    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return 'XDELEX';
    }

    /**
     * {@inheritdoc}
     */
    public function setArguments(array $arguments)
    {
        $processedArguments = [$arguments[0]];

        $argIndex = 1;
        if (isset($arguments[$argIndex]) && in_array(strtoupper($arguments[$argIndex]), ['KEEPREF', 'DELREF', 'ACKED'])) {
            $processedArguments[] = strtoupper($arguments[$argIndex]);
            $argIndex++;
        }

        while (isset($arguments[$argIndex])) {
            $arg = $arguments[$argIndex];

            if (is_array($arg)) {
                foreach ($arg as $item) {
                    $processedArguments[] = $item;
                }
            } else {
                $processedArguments[] = $arg;
            }

            $argIndex++;
        }

        parent::setArguments($processedArguments);
    }

    public function prefixKeys($prefix)
    {
        $this->applyPrefixForFirstArgument($prefix);
    }
}
