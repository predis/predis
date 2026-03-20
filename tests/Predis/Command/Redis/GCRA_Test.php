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

namespace Predis\Command\Redis;

use Predis\Command\PrefixableCommand;

/**
 * @group commands
 * @group realm-ratelimit
 */
class GCRA_Test extends PredisCommandTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getExpectedCommand(): string
    {
        return 'Predis\Command\Redis\GCRA';
    }

    /**
     * {@inheritdoc}
     */
    protected function getExpectedId(): string
    {
        return 'GCRA';
    }

    /**
     * @dataProvider argumentsProvider
     * @group disconnected
     */
    public function testFilterArguments(array $actualArguments, array $expectedArguments): void
    {
        $command = $this->getCommand();
        $command->setArguments($actualArguments);

        $this->assertSame($expectedArguments, $command->getArguments());
    }

    /**
     * @group disconnected
     */
    public function testParseResponse(): void
    {
        $raw = [0, 5, 4, -1, 10];
        $expected = [
            'limited' => 0,
            'maxRequests' => 5,
            'availableRequests' => 4,
            'retryAfter' => -1,
            'fullBurstAfter' => 10,
        ];
        $command = $this->getCommand();

        $this->assertSame($expected, $command->parseResponse($raw));
    }

    /**
     * @group disconnected
     */
    public function testPrefixKeys(): void
    {
        /** @var PrefixableCommand $command */
        $command = $this->getCommand();
        $actualArguments = ['mykey', 4, 10, 1.0];
        $prefix = 'prefix:';
        $expectedArguments = ['prefix:mykey', 4, 10, 1.0];

        $command->setArguments($actualArguments);
        $command->prefixKeys($prefix);

        $this->assertSame($expectedArguments, $command->getArguments());
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 8.8.0
     */
    public function testRequiredArgumentsDrainAllowanceByOne(): void
    {
        $redis = $this->getClient();

        // max_burst=4, requests_per_period=10, period=10 => 10 req/10s, emission_interval=1s
        // With max_burst=4, max-req-num = 4+1 = 5
        $result = $redis->gcra('ratelimit:user1', 4, 10, 10);
        $expectedResult = [
            'limited' => 0,
            'maxRequests' => 5,
            'availableRequests' => 4,
            'retryAfter' => -1,
            'fullBurstAfter' => 1,
        ];

        // First request: not limited
        $this->assertSame($expectedResult, $result);

        $result = $redis->gcra('ratelimit:user1', 4, 10, 10);
        $expectedResult = [
            'limited' => 0,
            'maxRequests' => 5,
            'availableRequests' => 3,
            'retryAfter' => -1,
            'fullBurstAfter' => 2,
        ];

        // Second request: allowance drained by 1
        $this->assertSame($expectedResult, $result);
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 8.8.0
     */
    public function testRequiredArgumentsDrainAllowanceByOneResp3(): void
    {
        $redis = $this->getResp3Client();

        // max_burst=4, requests_per_period=10, period=10 => 10 req/10s, emission_interval=1s
        // With max_burst=4, max-req-num = 4+1 = 5
        $result = $redis->gcra('ratelimit:user1', 4, 10, 10);
        $expectedResult = [
            'limited' => 0,
            'maxRequests' => 5,
            'availableRequests' => 4,
            'retryAfter' => -1,
            'fullBurstAfter' => 1,
        ];

        // First request: not limited
        $this->assertSame($expectedResult, $result);

        $result = $redis->gcra('ratelimit:user1', 4, 10, 10);
        $expectedResult = [
            'limited' => 0,
            'maxRequests' => 5,
            'availableRequests' => 3,
            'retryAfter' => -1,
            'fullBurstAfter' => 2,
        ];

        // Second request: allowance drained by 1
        $this->assertSame($expectedResult, $result);
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 8.8.0
     */
    public function testRequestsPerPeriodAndPeriodCombination(): void
    {
        $redis = $this->getClient();

        // max_burst=0, requests_per_period=1, period=60
        // Only 1 request allowed per 60 seconds, no burst
        // max-req-num = 0+1 = 1
        $result1 = $redis->gcra('ratelimit:strict', 0, 1, 60);
        $expectedResult1 = [
            'limited' => 0,
            'maxRequests' => 1,
            'availableRequests' => 0,
            'retryAfter' => -1,
            'fullBurstAfter' => 60,
        ];

        // First request: not limited
        $this->assertSame($expectedResult1, $result1);

        // Second request is limited (no burst, only 1/60s allowed)
        $result2 = $redis->gcra('ratelimit:strict', 0, 1, 60);
        $this->assertSame(1, $result2['limited']);
        $this->assertSame(1, $result2['maxRequests']);
        $this->assertSame(0, $result2['availableRequests']);
        $this->assertGreaterThan(0, $result2['retryAfter']); // retry-after > 0 (must wait)
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 8.8.0
     */
    public function testNumRequestsDrainsAllowanceByCount(): void
    {
        $redis = $this->getClient();

        // max_burst=9, requests_per_period=10, period=10 => max-req-num = 10
        // Use NUM_REQUESTS=3 to drain 3 at once
        $result1 = $redis->gcra('ratelimit:bulk', 9, 10, 10, 3);
        $expectedResult1 = [
            'limited' => 0,
            'maxRequests' => 10,
            'availableRequests' => 7,
            'retryAfter' => -1,
            'fullBurstAfter' => 3,
        ];

        // First request: not limited, 7 available (10 - 3)
        $this->assertSame($expectedResult1, $result1);

        // Another request with NUM_REQUESTS=3
        $result2 = $redis->gcra('ratelimit:bulk', 9, 10, 10, 3);
        $expectedResult2 = [
            'limited' => 0,
            'maxRequests' => 10,
            'availableRequests' => 4,
            'retryAfter' => -1,
            'fullBurstAfter' => 6,
        ];

        // Second request: not limited, 4 available (7 - 3)
        $this->assertSame($expectedResult2, $result2);

        // Another request with NUM_REQUESTS=3
        $result3 = $redis->gcra('ratelimit:bulk', 9, 10, 10, 3);
        $expectedResult3 = [
            'limited' => 0,
            'maxRequests' => 10,
            'availableRequests' => 1,
            'retryAfter' => -1,
            'fullBurstAfter' => 9,
        ];

        // Third request: not limited, 1 available (4 - 3)
        $this->assertSame($expectedResult3, $result3);

        // Next request with NUM_REQUESTS=3 should be limited (only 1 left)
        $result4 = $redis->gcra('ratelimit:bulk', 9, 10, 10, 3);
        $this->assertSame(1, $result4['limited']);
        $this->assertGreaterThan(0, $result4['retryAfter']); // must wait
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 8.8.0
     */
    public function testRetryAfterBehaviorAllowsRequestsAfterWaiting(): void
    {
        $redis = $this->getClient();

        // max_burst=2, requests_per_period=3, period=3 => 3 req/3s, emission_interval=1s
        // max-req-num = 2+1 = 3
        // Drain all allowance with 3 requests
        $redis->gcra('ratelimit:retry', 2, 3, 3);
        $redis->gcra('ratelimit:retry', 2, 3, 3);
        $redis->gcra('ratelimit:retry', 2, 3, 3);

        // Next request should be limited
        $limitedResult = $redis->gcra('ratelimit:retry', 2, 3, 3);
        $this->assertSame(1, $limitedResult['limited']);
        $this->assertGreaterThan(0, $limitedResult['retryAfter']);

        // Sleep for retryAfter seconds
        $retryAfterSeconds = $limitedResult['retryAfter'];
        sleep($retryAfterSeconds);

        // After waiting, request should no longer be limited
        $afterWaitResult = $redis->gcra('ratelimit:retry', 2, 3, 3);
        $this->assertSame(0, $afterWaitResult['limited']);
        $this->assertSame(-1, $afterWaitResult['retryAfter']);
    }

    public function argumentsProvider(): array
    {
        return [
            'with required arguments only' => [
                ['mykey', 4, 10, 1.0],
                ['mykey', 4, 10, 1.0],
            ],
            'with NUM_REQUESTS' => [
                ['mykey', 4, 10, 1.0, 5],
                ['mykey', 4, 10, 1.0, 'NUM_REQUESTS', 5],
            ],
            'with null NUM_REQUESTS (omitted)' => [
                ['mykey', 4, 10, 1.0, null],
                ['mykey', 4, 10, 1.0],
            ],
        ];
    }
}

