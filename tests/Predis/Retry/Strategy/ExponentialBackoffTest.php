<?php

/*
 * This file is part of the Predis package.
 *
 * (c) 2009-2020 Daniele Alessandri
 * (c) 2021-2026 Till Krüss
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

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
        $this->assertLessThanOrEqual(RetryStrategyInterface::DEFAULT_CAP, $backoff->compute(100));

        // Test default base
        $this->assertGreaterThanOrEqual(RetryStrategyInterface::DEFAULT_BASE, $backoff->compute(0));

        $interval = $backoff->compute(2);

        // Test between
        $this->assertGreaterThanOrEqual(RetryStrategyInterface::DEFAULT_BASE, $interval);
        $this->assertLessThanOrEqual(RetryStrategyInterface::DEFAULT_CAP, $interval);

        $backoff = new ExponentialBackoff(1000000, 10000000);

        // Test adjusted cap
        $this->assertLessThanOrEqual(10000000, $backoff->compute(100));

        // Test adjusted base
        $this->assertGreaterThanOrEqual(1000000, $backoff->compute(0));

        $backoff = new ExponentialBackoff(RetryStrategyInterface::DEFAULT_BASE, -1);

        // Test with no cap
        $this->assertEquals(RetryStrategyInterface::DEFAULT_BASE * 2, $backoff->compute(1));

        $backoff = new ExponentialBackoff(
            RetryStrategyInterface::DEFAULT_BASE,
            RetryStrategyInterface::DEFAULT_CAP,
            true
        );

        $interval = $backoff->compute(0);

        // Test with jitter - default base
        $this->assertGreaterThanOrEqual(0, $interval);
        $this->assertLessThanOrEqual(RetryStrategyInterface::DEFAULT_BASE, $interval);

        $interval = $backoff->compute(6);

        // Test with jitter - default cap
        $this->assertGreaterThanOrEqual(0, $interval);
        $this->assertLessThanOrEqual(RetryStrategyInterface::DEFAULT_CAP, $interval);
    }
}
