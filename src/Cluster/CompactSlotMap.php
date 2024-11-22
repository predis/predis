<?php

namespace Predis\Cluster;

use ArrayAccess;
use Countable;
use IteratorAggregate;

class CompactSlotMap
{
    /**
     * Slot ranges for each slot.
     *
     * @var array<int, SlotRange>
     */
    private array $slotRanges;

    public function __construct()
    {
        $this->reset();
    }

    public static function isValid($slot)
    {
        return $slot >= 0 && $slot <= SlotRange::MAX_SLOTS;
    }

    public static function isValidRange($first, $last)
    {
        return SlotRange::isValidRange($first, $last);
    }

    public function reset()
    {
        $this->slotRanges = [
            new SlotRange(0, SlotRange::MAX_SLOTS, ''),
        ];
    }

    public function setSlots($first, $last, $connection)
    {
        $targetSlotRange = new SlotRange($first, $last, (string) $connection);

        $merged = [];

        foreach ($this->slotRanges as $slotRange) {
            if (!$targetSlotRange || !$slotRange->hashIntersectionWith($targetSlotRange)) {
                $merged[] = $slotRange;
                continue;
            }

            $leftStart = $slotRange->getStart();
            $leftEnd = $targetSlotRange->getStart() - 1;

            if (SlotRange::isValidRange($leftStart, $leftEnd)) {
                $merged[] = new SlotRange($leftStart, $leftEnd, $slotRange->getConnection());
            }

            $middleStart = $targetSlotRange->getStart();
            $middleEnd = $slotRange->getEnd();

            if (SlotRange::isValidRange($middleStart, $middleEnd)) {
                $merged[] = new SlotRange($middleStart, $middleEnd, $targetSlotRange->getConnection());
            }

            $rightStart = $slotRange->getEnd() + 1;
            $rightEnd = $targetSlotRange->getEnd();

            if (SlotRange::isValidRange($rightStart, $rightEnd)) {
                $targetSlotRange = new SlotRange($rightStart, $rightEnd, $targetSlotRange->getConnection());
            } else {
                $targetSlotRange = null;
            }
        }

        // @TODO: Compact merged slot ranges.

        $this->slotRanges = $merged;
    }

    public function printSlotRanges()
    {
        foreach ($this->slotRanges as $slotRange) {
            printf(
                "[%d, %d] => %s\n",
                $slotRange->getStart(),
                $slotRange->getEnd(),
                $slotRange->getConnection()
            );
        }
    }
}