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
 * @see http://redis.io/commands/arscan
 */
class ARSCAN extends RedisCommand
{
    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return 'ARSCAN';
    }

    /**
     * {@inheritdoc}
     */
    public function setArguments(array $arguments)
    {
        // [key, start, end, ?limit]
        $processed = [$arguments[0], $arguments[1], $arguments[2]];

        if (isset($arguments[3]) && $arguments[3] !== null) {
            $processed[] = 'LIMIT';
            $processed[] = $arguments[3];
        }

        parent::setArguments($processed);
    }

    public function prefixKeys($prefix)
    {
        $this->applyPrefixForFirstArgument($prefix);
    }
}
