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

namespace Predis\Command\Redis\Search;

use Predis\Command\Command as RedisCommand;
use Predis\Command\Redis\CONFIG;

/**
 * @deprecated FT.CONFIG GET and SET is deprecated since Redis 8.0.
 * @see CONFIG if you want to manipulate search configuration
 *
 * @see https://redis.io/commands/ft.config-get/
 * @see https://redis.io/commands/ft.config-set/
 *
 * Container command corresponds to any FT.CONFIG *.
 * Represents any FUNCTION command with subcommand as first argument.
 */
class FTCONFIG extends RedisCommand
{
    public function getId()
    {
        return 'FT.CONFIG';
    }
}
