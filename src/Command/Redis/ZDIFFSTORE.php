<?php

namespace Predis\Command\Redis;

use Predis\Command\Command as RedisCommand;
use Predis\Command\Traits\Numkeys;
use Predis\Command\Traits\Keys;

class ZDIFFSTORE extends RedisCommand
{
    use Numkeys {
        setArguments as setNumkeys;
    }
    use Keys;

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
