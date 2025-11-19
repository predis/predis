<?php

namespace Predis\Retry\Strategy;

/**
 * Retry strategy interface
 */
interface StrategyInterface
{
    /**
     * Minimum backoff between each retry in micro seconds.
     */
    const DEFAULT_BASE = 8 * 1000;

    /**
     * Maximum backoff between each retry in micro seconds.
     */
    const DEFAULT_CAP = 512 * 1000;

    /**
     * Compute backoff in micro seconds upon failure.
     *
     * @param int $failures
     * @return mixed
     */
    public function compute(int $failures): int;
}
