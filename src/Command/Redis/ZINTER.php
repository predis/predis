<?php

namespace Predis\Command\Redis;

use Predis\Command\Traits\With\WithScores;

/**
 * @link https://redis.io/commands/zinter/
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
