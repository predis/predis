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
use Predis\Command\Traits\Timeout;
use Predis\Command\Traits\To\ServerTo;

class FAILOVER extends RedisCommand
{
    use ServerTo {
        ServerTo::setArguments as setTo;
    }
    use Timeout {
        Timeout::setArguments as setTimeout;
    }

    protected static $toArgumentPositionOffset = 0;
    protected static $timeoutArgumentPositionOffset = 2;

    public function getId()
    {
        return 'FAILOVER';
    }

    public function setArguments(array $arguments)
    {
        if (array_key_exists(1, $arguments) && false !== $arguments[1]) {
            $arguments[1] = 'ABORT';
        }

        $this->setTimeout($arguments);
        $arguments = $this->getArguments();

        $this->setTo($arguments);
        $this->filterArguments();
    }
}
