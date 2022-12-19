<?php

namespace Predis\Command\Redis\Json;

use Predis\Command\Command as RedisCommand;
use Predis\Command\Traits\Json\Indent;
use Predis\Command\Traits\Json\Newline;
use Predis\Command\Traits\Json\Space;

/**
 * @link https://redis.io/commands/json.get/
 *
 * Return the value at path in JSON serialized form
 */
class JSONGET extends RedisCommand
{
    use Indent {
        Indent::setArguments as setIndent;
    }
    use Newline {
        Newline::setArguments as setNewline;
    }
    use Space {
        Space::setArguments as setSpace;
    }

    protected static $indentArgumentPositionOffset = 1;
    protected static $newlineArgumentPositionOffset = 2;
    protected static $spaceArgumentPositionOffset = 3;

    public function getId()
    {
        return 'JSON.GET';
    }

    public function setArguments(array $arguments)
    {
        $this->setIndent($arguments);
        $arguments = $this->getArguments();

        $this->setNewline($arguments);
        $arguments = $this->getArguments();

        $this->setSpace($arguments);
        $this->filterArguments();
    }
}
