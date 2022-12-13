<?php

namespace Predis\Command\Redis;

use Predis\Command\Command as RedisCommand;
use Predis\Command\Traits\Keys;
use Predis\Command\Traits\Limit;

/**
 * @link https://redis.io/commands/zintercard/
 *
 * This command is similar to ZINTER, but instead of returning the result set,
 * it returns just the cardinality of the result.
 */
class ZINTERCARD extends RedisCommand
{
    use Keys {
        Keys::setArguments as setKeys;
    }
    use Limit {
        Limit::setArguments as setLimit;
    }

    protected static $keysArgumentPositionOffset = 0;
    protected static $limitArgumentPositionOffset = 1;

    public function getId()
    {
        return 'ZINTERCARD';
    }

    public function setArguments(array $arguments)
    {
        $this->setLimit($arguments);
        $arguments = $this->getArguments();

        $this->setKeys($arguments);
    }
}
