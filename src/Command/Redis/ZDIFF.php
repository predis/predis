<?php

namespace Predis\Command\Redis;

use Predis\Command\Command as RedisCommand;
use Predis\Command\Traits\Keys;
use Predis\Command\Traits\With\WithScores;

/**
 * @link https://redis.io/commands/zdiff/
 *
 * This command is similar to ZDIFFSTORE, but instead of
 * storing the resulting sorted set, it is returned to the client.
 */
class ZDIFF extends RedisCommand
{
    use WithScores {
        WithScores::setArguments as setWithScore;
    }
    use Keys {
        Keys::setArguments as setKeys;
    }

    protected static $keysArgumentPositionOffset = 0;

    public function getId()
    {
        return 'ZDIFF';
    }

    public function setArguments(array $arguments)
    {
        $this->setKeys($arguments);
        $arguments = $this->getArguments();

        $this->setWithScore($arguments);
    }
}
