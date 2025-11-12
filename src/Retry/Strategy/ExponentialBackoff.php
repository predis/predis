<?php

namespace Predis\Retry\Strategy;
class ExponentialBackoff implements StrategyInterface
{
    /**
     * @var float
     */
    protected $base;

    /**
     * @var float
     */
    protected $cap;

    /**
     * @var bool
     */
    protected $withJitter;

    public function __construct(float $base = self::DEFAULT_BASE, float $cap = self::DEFAULT_CAP, bool $withJitter = true)
    {
        $this->base = $base;
        $this->cap = $cap;
        $this->withJitter = $withJitter;
    }

    /**
     * @inheritDoc
     */
    public function compute(int $failures): float
    {
        if ($this->withJitter) {
            return min($this->cap, (mt_rand(0, mt_getrandmax() - 1) / mt_getrandmax()) * $this->base * 2**$failures);
        }

        return min($this->cap, $this->base * 2**$failures);
    }
}
