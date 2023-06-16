<?php

namespace Predis\Command;

/**
 * Represents command that could be called against cluster.
 */
interface Clusterable extends CommandInterface
{
    /**
     * Assign the specified slot to the command for clustering distribution.
     *
     * @param int $slot Slot ID.
     */
    public function setSlot(int $slot): void;

    /**
     * Returns the assigned slot of the command for clustering distribution.
     *
     * @return int
     */
    public function getSlot(): int;
}
