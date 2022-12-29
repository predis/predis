<?php

namespace Predis\Command\Redis;

use Predis\Command\Command as RedisCommand;
use Predis\Command\Traits\By\ByLexByScore;
use Predis\Command\Traits\Limit;
use Predis\Command\Traits\Rev;

/**
 * @link https://redis.io/commands/zrangestore/
 *
 * This command is like ZRANGE, but stores the result in the destination key.
 */
class ZRANGESTORE extends RedisCommand
{
    use ByLexByScore {
        ByLexByScore::setArguments as setByLexByScoreArgument;
    }
    use Rev {
        Rev::setArguments as setReversedArgument;
    }
    use Limit {
        Limit::setArguments as setLimitArguments;
    }

    protected static $byLexByScoreArgumentPositionOffset = 4;
    protected static $revArgumentPositionOffset = 5;
    protected static $limitArgumentPositionOffset = 6;

    public function getId()
    {
        return 'ZRANGESTORE';
    }

    public function setArguments(array $arguments)
    {
        $this->setByLexByScoreArgument($arguments);
        $arguments = $this->getArguments();

        $this->setReversedArgument($arguments);
        $arguments = $this->getArguments();

        $this->setLimitArguments($arguments);
        $this->filterArguments();
    }
}
