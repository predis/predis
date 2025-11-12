<?php

namespace Predis\Retry\Strategy;

/**
 * Retry strategy interface
 */
interface StrategyInterface
{
    /**
     * Minimum backoff between each retry in seconds.
     */
    const DEFAULT_BASE = 0.008;

    /**
     * Maximum backoff between each retry in seconds.
     */
    const DEFAULT_CAP = 0.512;

    /**
     * Compute backoff in seconds upon failure.
     *
     * @param int $failures
     * @return mixed
     */
    public function compute(int $failures): float;
}
