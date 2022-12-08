<?php

namespace Predis\Command\Redis;

use Predis\Command\Traits\WithScores;

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
