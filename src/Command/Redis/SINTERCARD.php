<?php

namespace Predis\Command\Redis;

use Predis\Command\Command as RedisCommand;
use Predis\Command\Traits\Keys;
use Predis\Command\Traits\Limit;

class SINTERCARD extends RedisCommand
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
        return 'SINTERCARD';
    }

    public function setArguments(array $arguments)
    {
        $this->setLimit($arguments);
        $arguments = $this->getArguments();

        $this->setKeys($arguments);
    }
}
