<?php

/*
 * This file is part of the Predis package.
 *
 * (c) 2009-2020 Daniele Alessandri
 * (c) 2021-2024 Till Krüss
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Command\Redis;

use Predis\Command\Command as RedisCommand;
use Predis\Command\Traits\DB;
use Predis\Command\Traits\Replace;

/**
 * @see https://redis.io/commands/copy/
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
