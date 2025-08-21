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

namespace Predis\Command\Redis\BloomFilter;

use Predis\Command\PrefixableCommand as RedisCommand;
use Predis\Command\Traits\BloomFilters\Capacity;
use Predis\Command\Traits\BloomFilters\Error;
use Predis\Command\Traits\BloomFilters\Expansion;
use Predis\Command\Traits\BloomFilters\Items;
use Predis\Command\Traits\BloomFilters\NoCreate;

class BFINSERT extends RedisCommand
{
    use Capacity {
        Capacity::setArguments as setCapacity;
    }
    use Error {
        Error::setArguments as setErrorRate;
    }
    use Expansion {
        Expansion::setArguments as setExpansion;
    }
    use Items {
        Items::setArguments as setItems;
    }
    use NoCreate {
        NoCreate::setArguments as setNoCreate;
    }

    protected static $capacityArgumentPositionOffset = 1;
    protected static $errorArgumentPositionOffset = 2;
    protected static $expansionArgumentPositionOffset = 3;
    protected static $noCreateArgumentPositionOffset = 4;
    protected static $itemsArgumentPositionOffset = 6;

    public function getId()
    {
        return 'BF.INSERT';
    }

    public function prefixKeys($prefix)
    {
        $this->applyPrefixForFirstArgument($prefix);
    }

    public function setArguments(array $arguments)
    {
        $this->setNoCreate($arguments);
        $arguments = $this->getArguments();

        if (array_key_exists(5, $arguments) && $arguments[5]) {
            $arguments[5] = 'NONSCALING';
        }

        $this->setItems($arguments);
        $arguments = $this->getArguments();

        $this->setExpansion($arguments);
        $arguments = $this->getArguments();

        $this->setErrorRate($arguments);
        $arguments = $this->getArguments();

        $this->setCapacity($arguments);
        $this->filterArguments();
    }
}
