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
use Predis\Command\Traits\By\ByArgument;
use Predis\Command\Traits\Get\Get;
use Predis\Command\Traits\Limit\LimitObject;
use Predis\Command\Traits\Sorting;

/**
 * @see https://redis.io/commands/sort_ro/
 *
 * Read-only variant of the SORT command.
 * It is exactly like the original SORT but refuses the STORE option
 * and can safely be used in read-only replicas.
 */
class SORT_RO extends RedisCommand
{
    use ByArgument {
        ByArgument::setArguments as setBy;
    }
    use LimitObject {
        LimitObject::setArguments as setLimit;
    }
    use Get {
        Get::setArguments as setGetArgument;
    }
    use Sorting {
        Sorting::setArguments as setSorting;
    }

    protected static $byArgumentPositionOffset = 1;
    protected static $getArgumentPositionOffset = 3;
    protected static $sortArgumentPositionOffset = 4;

    public function getId()
    {
        return 'SORT_RO';
    }

    public function setArguments(array $arguments)
    {
        $alpha = array_pop($arguments);

        if (is_bool($alpha) && $alpha) {
            $arguments[] = 'ALPHA';
        } elseif (!is_bool($alpha)) {
            $arguments[] = $alpha;
        }

        $this->setSorting($arguments);
        $arguments = $this->getArguments();

        $this->setGetArgument($arguments);
        $arguments = $this->getArguments();

        $this->setLimit($arguments);
        $arguments = $this->getArguments();

        $this->setBy($arguments);
        $this->filterArguments();
    }
}
