<?php

namespace Predis\Command\Redis;

use Predis\Command\Command as RedisCommand;
use Predis\Command\Traits\Numkeys;
use Predis\Command\Traits\WithScores;
use Predis\Command\Traits\Keys;

/**
 * @link https://redis.io/commands/zdiff/
 *
 * This command is similar to ZDIFFSTORE, but instead of
 * storing the resulting sorted set, it is returned to the client.
 */
class ZDIFF extends RedisCommand
{
    use Numkeys {
        Numkeys::setArguments as setNumkeys;
    }
    use WithScores {
        WithScores::setArguments as setWithScore;
    }
    use Keys;

    protected static $keysArgumentPositionOffset = 0;

    public function getId()
    {
        return 'ZDIFF';
    }

    public function setArguments(array $arguments)
    {
        $this->setNumkeys($arguments);
        $arguments = $this->getArguments();
        $this->unpackKeysArray(self::$keysArgumentPositionOffset + 1, $arguments);
        $this->setWithScore($arguments);
    }
}
