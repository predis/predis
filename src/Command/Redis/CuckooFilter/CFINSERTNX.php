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

/**
 * @see https://redis.io/commands/cf.insertnx/
 *
 * Adds one or more items to a cuckoo filter, allowing the filter
 * to be created with a custom capacity if it does not exist yet.
 */
class CFINSERTNX extends CFINSERT
{
    public function getId()
    {
        return 'CF.INSERTNX';
    }
}
