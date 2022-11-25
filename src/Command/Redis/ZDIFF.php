<?php

namespace Predis\Command\Redis;

use Predis\Command\Command as RedisCommand;
use Predis\Command\Traits\Numkeys;
use Predis\Command\Traits\WithScores;

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

    public function getId()
    {
        return 'ZDIFF';
    }

    public function setArguments(array $arguments)
    {
        $this->setNumkeys($arguments);
        $arguments = $this->getArguments();

        foreach ($arguments as $i => $value) {
            if (is_array($value)) {
                $argumentsBefore = array_slice($arguments, 0, $i);
                $argumentsAfter = array_slice($arguments,  ++$i);
                $arguments = array_merge($argumentsBefore, $value, $argumentsAfter);
            }
        }

        $this->setWithScore($arguments);
    }
}
