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

namespace Predis\Command\Redis\Json;

use Predis\Command\Command as RedisCommand;
use Predis\Command\Traits\Json\NxXxArgument;

/**
 * @see https://redis.io/commands/json.set/
 *
 * Set the JSON value at path in key
 */
class JSONSET extends RedisCommand
{
    use NxXxArgument {
        setArguments as setSubcommand;
    }

    protected static $nxXxArgumentPositionOffset = 3;

    public function getId()
    {
        return 'JSON.SET';
    }

    public function setArguments(array $arguments)
    {
        $this->setSubcommand($arguments);
        $this->filterArguments();
    }
}
