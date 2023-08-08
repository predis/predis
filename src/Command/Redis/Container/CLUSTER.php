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

namespace Predis\Command\Redis\Container;

use Predis\Response\Status;

/**
 * @method Status addSlotsRange(int ...$startEndSlots)
 * @method Status delSlotsRange(int ...$startEndSlots)
 * @method array  links()
 * @method array  shards()
 */
class CLUSTER extends AbstractContainer
{
    public function getContainerCommandId(): string
    {
        return 'CLUSTER';
    }
}
