<?php

namespace Predis\Retry;

use PHPUnit\Framework\TestCase;
use Predis\Retry\Strategy\EqualBackoff;
use Predis\Retry\Strategy\ExponentialBackoff;
use Predis\Retry\Strategy\NoBackoff;
use Predis\Retry\Strategy\StrategyInterface;
use Predis\TimeoutException;

class RetryTest extends TestCase
{
    /**
     * @group disconnected
     * @dataProvider strategyProvider
     * @throws Retryable
     */
    public function testCallWithRetry(
        StrategyInterface $backoffStrategy,
        int $retries,
        float $expectedExecutionTime,
        float $delta
    )
    {
        $retry = new Retry($backoffStrategy, $retries);
        $retriesCount = 0;

        $callable = function () use (&$retriesCount, $retries) {
            if ($retriesCount >= $retries) {
                return;
            }

            ++$retriesCount;
            throw new TimeoutException();
        };

        $startTime = microtime(true);
        $retry->callWithRetry($callable);
        $executionTime = microtime(true) - $startTime;

        $this->assertEquals($retriesCount, $retries);
        $this->assertEqualsWithDelta($expectedExecutionTime, $executionTime, $delta);

        $retry->updateRetriesCount(10);
        $this->assertEquals(10, $retry->getRetries());
    }

    public function strategyProvider(): array
    {
        return [
            'NoBackoff' => [
                new NoBackoff(),
                3,
                1,
                1
            ],
            'EqualBackoff' => [
                new EqualBackoff(0.3 * 1000000),
                3,
                0.9,
                0.1,
            ],
            'ExponentialBackoff - no jitter' => [
                new ExponentialBackoff(),
                3,
                0.112,
                0.05,
            ],
            'ExponentialBackoff - with jitter' => [
                new ExponentialBackoff(
                    StrategyInterface::DEFAULT_BASE,
                    StrategyInterface::DEFAULT_CAP,
                    true
                ),
                3,
                0.112,
                0.112, // Theoretically, jitter==0 might happen sequentially 3 times
            ],
        ];
    }
}
