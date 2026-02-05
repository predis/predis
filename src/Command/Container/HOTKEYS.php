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
     * Starts a hotkeys tracking on server side.
     *
     * @param  array<self::CPU|self::NET> $metrics  One of the available metric types. Check class constants.
     * @param  int|null                   $count    Number of top keys to report. Default: 10, Min: 10, Max: 64
     * @param  int|null                   $duration Auto-stop tracking after this many seconds. Default: 0 (no auto-stop)
     * @param  int|null                   $sample   Sample ratio - track keys with probability 1/sample. Default: 1 (track every key), Min: 1
     * @param  array<int>|null            $slots    All specified slots must be hosted by the receiving node! If not specified, all slots are tracked.
     * @return string|Status
     */
    public function start(array $metrics, ?int $count = null, ?int $duration = null, ?int $sample = null, ?array $slots = null)
    {
        return $this->__call('START', func_get_args());
    }
}
