<?php

namespace Predis\Retry;

use PHPUnit\Framework\TestCase;
use Predis\Connection\ConnectionException;
use Predis\Connection\NodeConnectionInterface;
use Predis\Connection\Resource\Exception\StreamInitException;
use Predis\Retry\Strategy\EqualBackoff;
use Predis\Retry\Strategy\ExponentialBackoff;
use Predis\Retry\Strategy\NoBackoff;
use Predis\Retry\Strategy\StrategyInterface;
use Predis\TimeoutException;
use Throwable;

class RetryTest extends TestCase
{
    /**
     * @group disconnected
     * @dataProvider strategyProvider
     * @throws Throwable
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

    /**
     * @group disconnected
     * @return void
     */
    public function testNoRetriesOnExcludedRetryableExceptions()
    {
        $retry = new Retry(new NoBackoff(), 3, [ConnectionException::class]);
        $retriesCount = 0;
        $callCount = 0;

        $doCallable = function () use (&$callCount) {
            ++$callCount;

            if ($callCount <= 3) {
                throw new TimeoutException();
            } else if ($callCount <= 7) {
                throw new ConnectionException(
                    $this->getMockBuilder(NodeConnectionInterface::class)->getMock()
                );
            } else {
                throw new StreamInitException();
            }
        };

        $failCallable = function () use (&$retriesCount) {
            ++$retriesCount;
        };

        # Ensures that no retries happens on excluded exception.
        while ($callCount < 3) {
            try {
                $retry->callWithRetry($doCallable, $failCallable);
            } catch (Throwable $e) {
                $this->assertInstanceOf(TimeoutException::class, $e);
                $this->assertEquals(0, $retriesCount);
            }
        }

        # Ensures that retries happens on specified exception.
        try {
            $retry->callWithRetry($doCallable, $failCallable);
        } catch (Throwable $e) {
            $this->assertInstanceOf(ConnectionException::class, $e);
            $this->assertEquals(3, $retriesCount);
        }

        $retry->updateCatchableExceptions([StreamInitException::class]);

        # Ensures that retries happens on updated catchable exceptions.
        try {
            $retry->callWithRetry($doCallable, $failCallable);
        } catch (Throwable $e) {
            $this->assertInstanceOf(StreamInitException::class, $e);
            $this->assertEquals(6, $retriesCount);
        }

        $this->assertEquals(11, $callCount);
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
