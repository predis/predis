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
