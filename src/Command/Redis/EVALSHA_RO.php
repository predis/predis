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

/**
 * @see https://redis.io/commands/evalsha_ro/
 *
 * This is a read-only variant of the EVALSHA command
 * that cannot execute commands that modify data.
 */
class EVALSHA_RO extends EVAL_RO
{
    public function getId()
    {
        return 'EVALSHA_RO';
    }
}
