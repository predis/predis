<?php

namespace Predis\Retry\Strategy;
class ExponentialBackoff implements StrategyInterface
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
     * @param int $base in micro seconds
     * @param int $cap in micro seconds
     * @param bool $withJitter
     */
    public function __construct(int $base = self::DEFAULT_BASE, int $cap = self::DEFAULT_CAP, bool $withJitter = false)
    {
        $this->base = $base;
        $this->cap = $cap;
        $this->withJitter = $withJitter;
    }

    /**
     * @inheritDoc
     */
    public function compute(int $failures): int
    {
        if ($this->withJitter) {
            return min($this->cap, (mt_rand(0, mt_getrandmax() - 1) / mt_getrandmax()) * ($this->base * 2**$failures));
        }

        return min($this->cap, $this->base * 2**$failures);
    }
}
