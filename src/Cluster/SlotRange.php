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

use Countable;
use OutOfBoundsException;

/**
 * Represents a range of slots in a Redis cluster.
 */
class SlotRange implements Countable
{
    /**
     * Maximum number of slots in a Redis cluster is 16384.
     */
    public const MAX_SLOTS = 0x3FFF;

    /**
     * Starting slot of the range.
     *
     * @var int
     */
    protected $start;

    /**
     * Ending slot of the range.
     *
     * @var int
     */
    protected $end;

    /**
     * Connection to the server hosting this slot range.
     *
     * @var string
     */
    protected $connection;

    public function __construct(int $start, int $end, string $connection)
    {
        if (!static::isValidRange($start, $end)) {
            throw new OutOfBoundsException("Invalid slot range $start-$end for `$connection`");
        }
        $this->start = $start;
        $this->end = $end;
        $this->connection = $connection;
    }

    /**
     * Checks if a slot range is valid.
     *
     * @param int $first
     * @param int $last
     *
     * @return bool
     */
    public static function isValidRange($first, $last)
    {
        return $first >= 0 && $first <= self::MAX_SLOTS && $last >= 0x0000 && $last <= self::MAX_SLOTS && $first <= $last;
    }

    /**
     * Returns the start slot index of this range.
     *
     * @return int
     */
    public function getStart()
    {
        return $this->start;
    }

    /**
     * Returns the end slot index of this range.
     *
     * @return int
     */
    public function getEnd()
    {
        return $this->end;
    }

    /**
     * Returns the connection to the server hosting this slot range.
     *
     * @return string
     */
    public function getConnection()
    {
        return $this->connection;
    }

    /**
     * Checks if the specific slot is contained in this range.
     *
     * @param int $slot
     *
     * @return bool
     */
    public function hasSlot(int $slot)
    {
        return $this->start <= $slot && $this->end >= $slot;
    }

    /**
     * Returns an array of connection strings for each slot in this range.
     *
     * @return string[]
     */
    public function toArray(): array
    {
        return array_fill($this->start, $this->end - $this->start + 1, $this->connection);
    }

    /**
     * Returns the number of slots in this range.
     *
     * @return int
     */
    public function count(): int
    {
        return $this->end - $this->start + 1;
    }

    /**
     * Checks if this range has an intersection with the given slot range.
     *
     * @param SlotRange $slotRange
     *
     * @return bool
     */
    public function hasIntersectionWith(SlotRange $slotRange): bool
    {
        return $this->start <= $slotRange->getEnd() && $this->end >= $slotRange->getStart();
    }
}
