<?php

namespace Predis\Command\Redis\Json;

use Predis\Command\Command as RedisCommand;
use Predis\Command\Traits\Json\NxXxSubcommand;

/**
 * @link https://redis.io/commands/json.set/
 *
 * Set the JSON value at path in key
 */
class JSONSET extends RedisCommand
{
    use NxXxSubcommand {
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
