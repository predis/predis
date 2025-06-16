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
 * @see http://redis.io/commands/xsetid
 */
class XSETID extends RedisCommand
{
    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return 'XSETID';
    }

    public function setArguments(array $arguments): void
    {
        $preparedArguments = array_slice($arguments, 0, 2);

        if (isset($arguments[2])) {
            array_push($preparedArguments, 'ENTRIESADDED', $arguments[2]);
        }

        if (isset($arguments[3])) {
            array_push($preparedArguments, 'MAXDELETEDID', $arguments[3]);
        }

        parent::setArguments($preparedArguments);
    }

    public function prefixKeys($prefix)
    {
        $this->applyPrefixForFirstArgument($prefix);
    }
}
