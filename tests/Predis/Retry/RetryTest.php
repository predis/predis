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

namespace Predis\Retry;

use PHPUnit\Framework\TestCase;
use Predis\Connection\ConnectionException;
use Predis\Connection\NodeConnectionInterface;
use Predis\Connection\Resource\Exception\StreamInitException;
use Predis\Retry\Strategy\EqualBackoff;
use Predis\Retry\Strategy\ExponentialBackoff;
use Predis\Retry\Strategy\NoBackoff;
use Predis\Retry\Strategy\RetryStrategyInterface;
use RuntimeException;
use Throwable;

class RetryTest extends TestCase
{
    /**
     * @group disconnected
     * @dataProvider strategyProvider
     * @throws Throwable
     */
    public function testCallWithRetry(
        RetryStrategyInterface $backoffStrategy,
        int $retries,
        float $expectedExecutionTime,
        float $delta
    ) {
        $retry = new Retry($backoffStrategy, $retries);
        $retriesCount = 0;

        $callable = function () use (&$retriesCount, $retries) {
            if ($retriesCount >= $retries) {
                return;
            }

            ++$retriesCount;
            throw new StreamInitException();
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
                throw new RuntimeException();
            } elseif ($callCount <= 7) {
                throw new ConnectionException(
                    $this->getMockBuilder(NodeConnectionInterface::class)->getMock()
                );
            }
            throw new StreamInitException();
        };

        $failCallable = function () use (&$retriesCount) {
            ++$retriesCount;
        };

        // Ensures that no retries happens on excluded exception.
        while ($callCount < 3) {
            try {
                $retry->callWithRetry($doCallable, $failCallable);
            } catch (Throwable $e) {
                $this->assertInstanceOf(RuntimeException::class, $e);
                $this->assertEquals(0, $retriesCount);
            }
        }

        // Ensures that retries happens on specified exception.
        try {
            $retry->callWithRetry($doCallable, $failCallable);
        } catch (Throwable $e) {
            $this->assertInstanceOf(ConnectionException::class, $e);
            $this->assertEquals(3, $retriesCount);
        }

        $retry->updateCatchableExceptions([StreamInitException::class]);

        // Ensures that retries happens on updated catchable exceptions.
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
                1,
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
                0.08,
            ],
            'ExponentialBackoff - with jitter' => [
                new ExponentialBackoff(
                    RetryStrategyInterface::DEFAULT_BASE,
                    RetryStrategyInterface::DEFAULT_CAP,
                    true
                ),
                3,
                0.112,
                0.112, // Theoretically, jitter==0 might happen sequentially 3 times
            ],
        ];
    }
}
