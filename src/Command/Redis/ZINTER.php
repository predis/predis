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

use Predis\Command\Traits\With\WithScores;

/**
 * @see https://redis.io/commands/zinter/
 *
 * This command is similar to ZINTERSTORE, but instead of
 * storing the resulting sorted set, it is returned to the client.
 */
class ZINTER extends ZINTERSTORE
{
    use WithScores;

    protected static $keysArgumentPositionOffset = 0;
    protected static $weightsArgumentPositionOffset = 1;
    protected static $aggregateArgumentPositionOffset = 2;

    public function getId()
    {
        return 'ZINTER';
    }
}
