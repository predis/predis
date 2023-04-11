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

namespace Predis\Command\Redis\CuckooFilter;

use Predis\Command\Command as RedisCommand;
use Predis\Command\Traits\BloomFilters\BucketSize;
use Predis\Command\Traits\BloomFilters\Expansion;
use Predis\Command\Traits\BloomFilters\MaxIterations;

class CFRESERVE extends RedisCommand
{
    use BucketSize {
        BucketSize::setArguments as setBucketSize;
    }
    use MaxIterations {
        MaxIterations::setArguments as setMaxIterations;
    }
    use Expansion {
        Expansion::setArguments as setExpansion;
    }

    protected static $bucketSizeArgumentPositionOffset = 2;
    protected static $maxIterationsArgumentPositionOffset = 3;
    protected static $expansionArgumentPositionOffset = 4;

    public function getId()
    {
        return 'CF.RESERVE';
    }

    public function setArguments(array $arguments)
    {
        $this->setExpansion($arguments);
        $arguments = $this->getArguments();

        $this->setMaxIterations($arguments);
        $arguments = $this->getArguments();

        $this->setBucketSize($arguments);
        $this->filterArguments();
    }
}
