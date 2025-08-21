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
 * @see https://redis.io/commands/bzmpop/
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
