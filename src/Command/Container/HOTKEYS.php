<?php

namespace Predis\Command\Container;

use Predis\Response\Status;

/**
 * @method Status stop()
 * @method Status reset()
 * @method array  get()
 */
class HOTKEYS extends AbstractContainer
{
    public const CPU = 'CPU';
    public const NET = 'NET';

    /**
     * @return string
     */
    public function getContainerCommandId(): string
    {
        return 'HOTKEYS';
    }

    /**
     * Starts a hotkeys tracking on server side
     *
     * @param array<self::CPU|self::NET> $metrics One of the available metric types. Check class constants.
     * @param int|null $count
     * @param int|null $duration
     * @param int|null $sample
     * @param array<int>|null $slots
     * @return Status
     */
    public function start(array $metrics, int $count = null, int $duration = null, int $sample = null, array $slots = null): Status
    {
        return $this->__call('START', func_get_args());
    }
}
