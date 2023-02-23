<?php

/*
 * This file is part of the Predis package.
 *
 * (c) 2009-2020 Daniele Alessandri
 * (c) 2021-2023 Till Krüss
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Command\Redis;

use Predis\Command\Command as RedisCommand;
use Predis\Command\Traits\Keys;
use Predis\Command\Traits\Limit\Limit;

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
