<?php

namespace Predis\Retry\Strategy;

use PHPUnit\Framework\TestCase;

class ExponentialBackoffTest extends TestCase
{
    /**
     * @group disconnected
     * @return void
     */
    public function testCompute(): void
    {
        $backoff = new ExponentialBackoff();

        // Test default cap
        $this->assertLessThanOrEqual(StrategyInterface::DEFAULT_CAP, $backoff->compute(100));

        // Test default base
        $this->assertGreaterThanOrEqual(StrategyInterface::DEFAULT_BASE, $backoff->compute(0));

        $interval = $backoff->compute(2);

        // Test between
        $this->assertGreaterThanOrEqual(StrategyInterface::DEFAULT_BASE, $interval);
        $this->assertLessThanOrEqual(StrategyInterface::DEFAULT_CAP, $interval);

        $backoff = new ExponentialBackoff(1000000, 10000000);

        // Test adjusted cap
        $this->assertLessThanOrEqual(10000000, $backoff->compute(100));

        // Test adjusted base
        $this->assertGreaterThanOrEqual(1000000, $backoff->compute(0));

        $backoff = new ExponentialBackoff(
            StrategyInterface::DEFAULT_BASE,
            StrategyInterface::DEFAULT_CAP,
            true
        );

        $interval = $backoff->compute(0);

        // Test with jitter - default base
        $this->assertGreaterThanOrEqual(0, $interval);
        $this->assertLessThanOrEqual(StrategyInterface::DEFAULT_BASE, $interval);

        $interval = $backoff->compute(6);
        var_dump($interval);

        // Test with jitter - default cap
        $this->assertGreaterThanOrEqual(0, $interval);
        $this->assertLessThanOrEqual(StrategyInterface::DEFAULT_CAP, $interval);
    }
}
