<?php

namespace Predis\Cluster;

use OutOfBoundsException;

class SlotRange
{
    public const MAX_SLOTS = 0x3FFF;

    protected int $start;
    protected int $end;
    protected string $connection;

    public function __construct(int $start, int $end, string $connection)
    {
        if (!static::isValidRange($start, $end)) {
            throw new OutOfBoundsException("Invalid slot range $start-$end for `$connection`");
        }
        $this->start = $start;
        $this->end = $end;
        $this->connection = $connection;
    }

    public static function isValidRange($first, $last)
    {
        return $first >= 0 && $first <= self::MAX_SLOTS && $last >= 0x0000 && $last <= self::MAX_SLOTS && $first <= $last;
    }

    public function getStart()
    {
        return $this->start;
    }

    public function getEnd()
    {
        return $this->end;
    }

    public function getConnection()
    {
        return $this->connection;
    }

    // public function equals(SlotRange $slotRange): bool
    // {
    //     return $this->start === $slotRange->getStart() && $this->end === $slotRange->getEnd();
    // }

    // public function contains(SlotRange $slotRange): bool
    // {
    //     return $this->start < $slotRange->getStart() && $this->end > $slotRange->getEnd();
    // }

    // public function isContainedBy(SlotRange $slotRange): bool
    // {
    //     return $this->start > $slotRange->getStart() && $this->end < $slotRange->getEnd();
    // }

    public function hashIntersectionWith(SlotRange $slotRange): bool
    {
        return $this->start <= $slotRange->getEnd() && $this->end >= $slotRange->getStart();
    }

    // public function intersect(SlotRange $slotRange, bool $useSource = false): SlotRange|false
    // {
    //     if (!$this->hashIntersectionWith($slotRange)) {
    //         return false;
    //     }
    //     $connection = $useSource ? $this->connection : $slotRange->getConnection();
    //     return new static(max($this->start, $slotRange->getStart()), min($this->end, $slotRange->getEnd()), $connection);
    // }
    
    // public function diff(SlotRange $slotRange, bool $useSource = true): SlotRange
    // {
    //     if (!$this->hashIntersectionWith($slotRange)) {
    //         return $useSource ? $this : new static($this->start, $this->end, $slotRange->getConnection());
    //     }

    //     return new static(
    //         min($this->start, $slotRange->getStart()),
    //         max($this->start, $slotRange->getStart()),
    //         $useSource ? $this->connection : $slotRange->getConnection()
    //     );
    // }

}
