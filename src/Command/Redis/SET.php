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
 * @see http://redis.io/commands/set
 */
class SET extends RedisCommand
{
    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return 'SET';
    }

    public function setArguments(array $arguments)
    {
        foreach ($arguments as $index => $value) {
            if ($index < 2) {
                continue;
            }

            if (false === $value || null === $value) {
                unset($arguments[$index]);
            }
        }

        parent::setArguments($arguments);
    }

    public function prefixKeys($prefix)
    {
        $this->applyPrefixForFirstArgument($prefix);
    }
}
