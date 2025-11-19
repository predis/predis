<?php

namespace Predis\Retry\Strategy;

/**
 * Equal backoff between retry
 */
class EqualBackoff implements StrategyInterface
{
    /**
     * @var int
     */
    protected $backoff;

    /**
     * @param int $backoff in micro seconds
     */
    public function __construct(int $backoff)
    {
        $this->backoff = $backoff;
    }

    public function compute(int $failures): int
    {
        return $this->backoff;
    }
}
