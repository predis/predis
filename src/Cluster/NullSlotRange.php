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

namespace Predis\Cluster;

/**
 * Represents the gap between slot ranges.
 */
class NullSlotRange extends SlotRange
{
    public function __construct(int $start, int $end)
    {
        parent::__construct($start, $end, '');
    }

    /**
     * {@inheritDoc}
     */
    public function toArray(): array
    {
        return [];
    }

    /**
     * {@inheritDoc}
     */
    public function count(): int
    {
        return 0;
    }
}
