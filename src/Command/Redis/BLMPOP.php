<?php

namespace Predis\Command\Redis;

class BLMPOP extends LMPOP
{
    protected static $keysArgumentPositionOffset = 1;
    protected static $leftRightArgumentPositionOffset = 2;
    protected static $countArgumentPositionOffset = 3;

    public function getId()
    {
        return 'BLMPOP';
    }
}
