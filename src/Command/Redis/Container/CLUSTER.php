<?php

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
