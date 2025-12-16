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

class ExponentialBackoff implements RetryStrategyInterface
{
    /**
     * @var int
     */
    protected $base;

    /**
     * @var int
     */
    protected $cap;

    /**
     * @var bool
     */
    protected $withJitter;

    /**
     * @param int  $base       in micro seconds
     * @param int  $cap        in micro seconds
     * @param bool $withJitter
     */
    public function __construct(int $base = self::DEFAULT_BASE, int $cap = self::DEFAULT_CAP, bool $withJitter = false)
    {
        $this->base = $base;
        $this->cap = $cap;
        $this->withJitter = $withJitter;
    }

    /**
     * {@inheritDoc}
     */
    public function compute(int $failures): int
    {
        if ($this->withJitter) {
            return min($this->cap, (mt_rand(0, mt_getrandmax() - 1) / mt_getrandmax()) * ($this->base * 2 ** $failures));
        }

        if ($this->cap > 0) {
            return min($this->cap, $this->base * 2 ** $failures);
        }

        return $this->base * 2 ** $failures;
    }

    /**
     * @return int
     */
    public function getBase(): int
    {
        return $this->base;
    }

    /**
     * @return int
     */
    public function getCap(): int
    {
        return $this->cap;
    }
}
