<?php

namespace Predis\Command\Redis;

/**
 * @link https://redis.io/commands/bzmpop/
 *
 * BZMPOP is the blocking variant of ZMPOP.
 */
class BZMPOP extends ZMPOP
{
    protected static $keysArgumentPositionOffset = 1;
    protected static $countArgumentPositionOffset = 3;
    protected static $modifierArgumentPositionOffset = 2;

    public function getId()
    {
        return 'BZMPOP';
    }
}
