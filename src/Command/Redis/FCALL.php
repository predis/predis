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
use Predis\Command\Traits\Keys;

/**
 * @see https://redis.io/commands/fcall/
 *
 * Invoke a function.
 */
class FCALL extends RedisCommand
{
    use Keys;

    protected static $keysArgumentPositionOffset = 1;

    public function getId()
    {
        return 'FCALL';
    }

    public function prefixKeys($prefix)
    {
        $arguments = $this->getArguments();

        if (isset($arguments[1])) {
            $numkeys = $arguments[1];

            for ($i = 2; $i < $numkeys + 2; $i++) {
                $arguments[$i] = $prefix . $arguments[$i];
            }
        }

        $this->setRawArguments($arguments);
    }
}
