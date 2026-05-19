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
 * @see http://redis.io/commands/arlastitems
 */
class ARLASTITEMS extends RedisCommand
{
    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return 'ARLASTITEMS';
    }

    /**
     * {@inheritdoc}
     */
    public function setArguments(array $arguments)
    {
        $processed = [$arguments[0], $arguments[1]];

        if (!empty($arguments[2])) {
            $processed[] = 'REV';
        }

        parent::setArguments($processed);
    }

    public function prefixKeys($prefix)
    {
        $this->applyPrefixForFirstArgument($prefix);
    }
}
