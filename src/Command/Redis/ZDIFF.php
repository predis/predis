<?php

namespace Predis\Command\Redis;

use Predis\Command\Command as RedisCommand;
use Predis\Command\Traits\WithScores;

/**
 * @link https://redis.io/commands/zdiff/
 *
 * This command is similar to ZDIFFSTORE, but instead of
 * storing the resulting sorted set, it is returned to the client.
 */
class ZDIFF extends RedisCommand
{
    use WithScores {
        setArguments as setWithScore;
    }

    public function getId()
    {
        return 'ZDIFF';
    }

    public function setArguments(array $arguments)
    {
        for ($i = 0; $i < count($arguments) - 1; $i++) {
            if (is_array($arguments[$i])) {
                $argumentsBefore = array_slice($arguments, 0, $i);
                $argumentsAfter = array_slice($arguments, -1, $i);
                $arguments = array_merge($argumentsBefore, $arguments[$i], $argumentsAfter);
            }
        }

        $this->setWithScore($arguments);
    }
}
