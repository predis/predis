<?php

namespace Predis\Command\Redis;
use Predis\Command\Command as RedisCommand;
use Predis\Command\Traits\DB;
use Predis\Command\Traits\Replace;

/**
 * @link https://redis.io/commands/copy/
 *
 * This command copies the value stored at the source key to the destination key.
 */
class COPY extends RedisCommand
{
    use DB {
        DB::setArguments as setDB;
    }
    use Replace {
        Replace::setArguments as setReplace;
    }

    protected static $dbArgumentPositionOffset = 2;

    public function getId()
    {
        return 'COPY';
    }

    public function setArguments(array $arguments)
    {
        $this->setDB($arguments);
        $arguments = $this->getArguments();

        $this->setReplace($arguments);
    }
}
