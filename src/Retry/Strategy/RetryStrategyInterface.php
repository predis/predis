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

namespace Predis\Retry\Strategy;

/**
 * Retry strategy interface.
 */
interface RetryStrategyInterface
{
    /**
     * Minimum backoff between each retry in micro seconds.
     */
    public const DEFAULT_BASE = 8 * 1000;

    /**
     * Maximum backoff between each retry in micro seconds.
     */
    public const DEFAULT_CAP = 512 * 1000;

    /**
     * Compute backoff in micro seconds upon failure.
     *
     * @param  int $failures
     * @return int
     */
    public function compute(int $failures): int;
}
