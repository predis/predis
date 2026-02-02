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
     * @param  int|null                   $count
     * @param  int|null                   $duration
     * @param  int|null                   $sample
     * @param  array<int>|null            $slots
     * @return string|Status
     */
    public function start(array $metrics, ?int $count = null, ?int $duration = null, ?int $sample = null, ?array $slots = null)
    {
        return $this->__call('START', func_get_args());
    }
}
