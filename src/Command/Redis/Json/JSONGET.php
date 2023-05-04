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

namespace Predis\Command\Redis\Json;

use Predis\Command\Command as RedisCommand;
use Predis\Command\Traits\Json\Indent;
use Predis\Command\Traits\Json\Newline;
use Predis\Command\Traits\Json\Space;

/**
 * @see https://redis.io/commands/json.get/
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
        $this->setSpace($arguments);
        $arguments = $this->getArguments();

        $this->setNewline($arguments);
        $arguments = $this->getArguments();

        $this->setIndent($arguments);
        $this->filterArguments();
    }
}
