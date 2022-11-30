<?php

namespace Predis\Command\Redis;

use Predis\Command\Command as RedisCommand;
use Predis\Command\Traits\Numkeys;
use Predis\Command\Traits\Keys;

/**
 * @link https://redis.io/commands/zdiffstore/
 *
 * Computes the difference between the first and all successive input sorted sets
 * and stores the result in destination. The total number of input keys is specified by numkeys.
 *
 * Keys that do not exist are considered to be empty sets.
 *
 * If destination already exists, it is overwritten.
 */
class ZDIFFSTORE extends RedisCommand
{
    use Keys;
    use Numkeys {
        setArguments as setNumkeys;
    }

    public static $keysArgumentPositionOffset = 1;

    public function getId()
    {
        return 'ZDIFFSTORE';
    }

    public function setArguments(array $arguments)
    {
        $this->setNumkeys($arguments);
        $arguments = $this->getArguments();
        $this->unpackKeysArray(self::$keysArgumentPositionOffset + 1, $arguments);
        parent::setArguments($arguments);
    }
}
