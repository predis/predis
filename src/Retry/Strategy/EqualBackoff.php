<?php

namespace Predis\Retry\Strategy;

/**
 * Equal backoff between retry
 */
class EqualBackoff implements StrategyInterface
{
    /**
     * @var float
     */
    protected $backoff;
    public function __construct(float $backoff)
    {
        $this->backoff = $backoff;
    }

    public function compute(int $failures): float
    {
        return $this->backoff;
    }
}
