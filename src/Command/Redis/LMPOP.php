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
use Predis\Command\Traits\Count;
use Predis\Command\Traits\Keys;
use Predis\Command\Traits\LeftRight;

class LMPOP extends RedisCommand
{
    use Keys {
        Keys::setArguments as setKeys;
    }
    use LeftRight {
        LeftRight::setArguments as setLeftRight;
    }
    use Count {
        Count::setArguments as setCount;
    }

    protected static $keysArgumentPositionOffset = 0;
    protected static $leftRightArgumentPositionOffset = 1;
    protected static $countArgumentPositionOffset = 2;

    public function getId()
    {
        return 'LMPOP';
    }

    public function setArguments(array $arguments)
    {
        $this->setCount($arguments);
        $arguments = $this->getArguments();

        $this->setLeftRight($arguments);
        $arguments = $this->getArguments();

        $this->setKeys($arguments);
        $this->filterArguments();
    }

    public function parseResponse($data)
    {
        if (null === $data) {
            return null;
        }

        return [$data[0] => $data[1]];
    }
}
