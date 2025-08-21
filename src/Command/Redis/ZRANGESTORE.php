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

use Predis\Command\Command as RedisCommand;
use Predis\Command\Traits\By\ByLexByScore;
use Predis\Command\Traits\Limit\Limit;
use Predis\Command\Traits\Rev;

/**
 * @see https://redis.io/commands/zrangestore/
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
