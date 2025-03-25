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

use ArrayAccess;
use ArrayIterator;
use Countable;
use IteratorAggregate;
use OutOfBoundsException;
use Predis\Connection\NodeConnectionInterface;
use ReturnTypeWillChange;
use Traversable;

/**
 * Compact slot map for redis-cluster.
 */
class SlotMap implements ArrayAccess, IteratorAggregate, Countable
{
    /**
     * Slot ranges list.
     *
     * @var SlotRange[]
     */
    private $slotRanges = [];

    /**
     * Checks if the given slot is valid.
     *
     * @param int $slot Slot index.
     *
     * @return bool
     */
    public static function isValid($slot)
    {
        return $slot >= 0 && $slot <= SlotRange::MAX_SLOTS;
    }

    /**
     * Checks if the given slot range is valid.
     *
     * @param int $first Initial slot of the range.
     * @param int $last  Last slot of the range.
     *
     * @return bool
     */
    public static function isValidRange($first, $last)
    {
        return SlotRange::isValidRange($first, $last);
    }

    /**
     * Resets the slot map.
     */
    public function reset()
    {
        $this->slotRanges = [];
    }

    /**
     * Checks if the slot map is empty.
     *
     * @return bool
     */
    public function isEmpty()
    {
        return empty($this->slotRanges);
    }

    /**
     * Returns the current slot map as a dictionary of $slot => $node.
     *
     * The order of the slots in the dictionary is not guaranteed.
     *
     * @return array
     */
    public function toArray()
    {
        return array_reduce(
            $this->slotRanges,
            function ($carry, $slotRange) {
                return $carry + $slotRange->toArray();
            },
            []
        );
    }

    /**
     * Returns the list of unique nodes in the slot map.
     *
     * @return array
     */
    public function getNodes()
    {
        return array_unique(array_map(
            function ($slotRange) {
                return $slotRange->getConnection();
            },
            $this->slotRanges
        ));
    }

    /**
     * Returns the list of slot ranges.
     *
     * @return SlotRange[]
     */
    public function getSlotRanges()
    {
        return $this->slotRanges;
    }

    /**
     * Assigns the specified slot range to a node.
     *
     * @param int                            $first      Initial slot of the range.
     * @param int                            $last       Last slot of the range.
     * @param NodeConnectionInterface|string $connection ID or connection instance.
     *
     * @throws OutOfBoundsException
     */
    public function setSlots($first, $last, $connection)
    {
        if (!static::isValidRange($first, $last)) {
            throw new OutOfBoundsException("Invalid slot range $first-$last for `$connection`");
        }

        $targetSlotRange = new SlotRange($first, $last, (string) $connection);

        // Get gaps of slot ranges list.
        $gaps = $this->getGaps($this->slotRanges);

        $results = $this->slotRanges;

        foreach ($gaps as $gap) {
            if (!$gap->hasIntersectionWith($targetSlotRange)) {
                continue;
            }

            // Get intersection of the gap and target slot range.
            $results[] = new SlotRange(
                max($gap->getStart(), $targetSlotRange->getStart()),
                min($gap->getEnd(), $targetSlotRange->getEnd()),
                $targetSlotRange->getConnection()
            );
        }

        $this->sortSlotRanges($results);

        $results = $this->compactSlotRanges($results);

        $this->slotRanges = $results;
    }

    /**
     * Returns the specified slot range.
     *
     * @param int $first Initial slot of the range.
     * @param int $last  Last slot of the range.
     *
     * @return array<int, string>
     */
    public function getSlots($first, $last)
    {
        if (!static::isValidRange($first, $last)) {
            throw new OutOfBoundsException("Invalid slot range $first-$last");
        }

        $placeHolder = new NullSlotRange($first, $last);

        $intersections = [];
        foreach ($this->slotRanges as $slotRange) {
            if (!$placeHolder->hasIntersectionWith($slotRange)) {
                continue;
            }

            $intersections[] = new SlotRange(
                max($placeHolder->getStart(), $slotRange->getStart()),
                min($placeHolder->getEnd(), $slotRange->getEnd()),
                $slotRange->getConnection()
            );
        }

        return array_reduce(
            $intersections,
            function ($carry, $slotRange) {
                return $carry + $slotRange->toArray();
            },
            []
        );
    }

    /**
     * Checks if the specified slot is assigned.
     *
     * @param int $slot Slot index.
     *
     * @return bool
     */
    #[ReturnTypeWillChange]
    public function offsetExists($slot)
    {
        return $this->findRangeBySlot($slot) !== false;
    }

    /**
     * Returns the node assigned to the specified slot.
     *
     * @param int $slot Slot index.
     *
     * @return string|null
     */
    #[ReturnTypeWillChange]
    public function offsetGet($slot)
    {
        $found = $this->findRangeBySlot($slot);

        return $found ? $found->getConnection() : null;
    }

    /**
     * Assigns the specified slot to a node.
     *
     * @param int                            $slot       Slot index.
     * @param NodeConnectionInterface|string $connection ID or connection instance.
     *
     * @return void
     */
    #[ReturnTypeWillChange]
    public function offsetSet($slot, $connection)
    {
        if (!static::isValid($slot)) {
            throw new OutOfBoundsException("Invalid slot $slot for `$connection`");
        }

        $this->offsetUnset($slot);
        $this->setSlots($slot, $slot, $connection);
    }

    /**
     * Returns the node assigned to the specified slot.
     *
     * @param int $slot Slot index.
     *
     * @return void
     */
    #[ReturnTypeWillChange]
    public function offsetUnset($slot)
    {
        if (!static::isValid($slot)) {
            throw new OutOfBoundsException("Invalid slot $slot");
        }

        $results = [];
        foreach ($this->slotRanges as $slotRange) {
            if (!$slotRange->hasSlot($slot)) {
                $results[] = $slotRange;
            }

            if (static::isValidRange($slotRange->getStart(), $slot - 1)) {
                $results[] = new SlotRange($slotRange->getStart(), $slot - 1, $slotRange->getConnection());
            }

            if (static::isValidRange($slot + 1, $slotRange->getEnd())) {
                $results[] = new SlotRange($slot + 1, $slotRange->getEnd(), $slotRange->getConnection());
            }
        }

        $this->slotRanges = $results;
    }

    /**
     * Returns the current number of assigned slots.
     *
     * @return int
     */
    #[ReturnTypeWillChange]
    public function count()
    {
        return array_sum(array_map(
            function ($slotRange) {
                return $slotRange->count();
            },
            $this->slotRanges
        ));
    }

    /**
     * Returns an iterator over the slot map.
     *
     * @return Traversable<int, string>
     */
    #[ReturnTypeWillChange]
    public function getIterator()
    {
        return new ArrayIterator($this->toArray());
    }

    /**
     * Find the slot range which contains the specific slot index.
     *
     * @param int $slot Slot index.
     *
     * @return SlotRange|false The slot range object or false if not found.
     */
    protected function findRangeBySlot(int $slot)
    {
        foreach ($this->slotRanges as $slotRange) {
            if ($slotRange->hasSlot($slot)) {
                return $slotRange;
            }
        }

        return false;
    }

    /**
     * Get gaps between sorted slot ranges with NullSlotRange object.
     *
     * @param SlotRange[] $slotRanges
     *
     * @return SlotRange[]
     */
    protected function getGaps(array $slotRanges)
    {
        if (empty($slotRanges)) {
            return [
                new NullSlotRange(0, SlotRange::MAX_SLOTS),
            ];
        }
        $gaps = [];
        $count = count($slotRanges);
        $i = 0;
        foreach ($slotRanges as $key => $slotRange) {
            $start = $slotRange->getStart();
            $end = $slotRange->getEnd();
            if (static::isValidRange($i, $start - 1)) {
                $gaps[] = new NullSlotRange($i, $start - 1);
            }

            $i = $end + 1;

            if ($key === $count - 1) {
                if (static::isValidRange($i, SlotRange::MAX_SLOTS)) {
                    $gaps[] = new NullSlotRange($i, SlotRange::MAX_SLOTS);
                }
            }
        }

        return $gaps;
    }

    /**
     * Sort slot ranges by start index.
     *
     * @param SlotRange[] $slotRanges
     *
     * @return void
     */
    protected function sortSlotRanges(array &$slotRanges)
    {
        usort(
            $slotRanges,
            function (SlotRange $a, SlotRange $b) {
                if ($a->getStart() == $b->getStart()) {
                    return 0;
                }

                return $a->getStart() < $b->getStart() ? -1 : 1;
            }
        );
    }

    /**
     * Compact adjacent slot ranges with the same connection.
     *
     * @param SlotRange[] $slotRanges
     *
     * @return SlotRange[]
     */
    protected function compactSlotRanges(array $slotRanges)
    {
        if (empty($slotRanges)) {
            return [];
        }

        $compacted = [];
        $count = count($slotRanges);
        $i = 0;
        $carry = $slotRanges[0];
        while ($i < $count) {
            $next = $slotRanges[$i + 1] ?? null;
            if (
                !is_null($next)
                && ($carry->getEnd() + 1) === $next->getStart()
                && $carry->getConnection() === $next->getConnection()
            ) {
                $carry = new SlotRange($carry->getStart(), $next->getEnd(), $carry->getConnection());
            } else {
                $compacted[] = $carry;
                $carry = $next;
            }
            $i++;
        }

        return array_values($compacted);
    }
}
