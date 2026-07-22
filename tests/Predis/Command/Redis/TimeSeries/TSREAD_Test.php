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

namespace Predis\Command\Redis\TimeSeries;

use Predis\Command\Argument\TimeSeries\ReadArguments;
use Predis\Command\Redis\PredisCommandTestCase;
use Predis\Response\ServerException;

/**
 * @group commands
 * @group realm-stack
 */
class TSREAD_Test extends PredisCommandTestCase
{
    /**
     * {@inheritDoc}
     */
    protected function getExpectedCommand(): string
    {
        return TSREAD::class;
    }

    /**
     * {@inheritDoc}
     */
    protected function getExpectedId(): string
    {
        return 'TSREAD';
    }

    /**
     * @group disconnected
     * @dataProvider argumentsProvider
     */
    public function testFilterArguments(array $actualArguments, array $expectedArguments): void
    {
        $command = $this->getCommand();
        $command->setArguments($actualArguments);

        $this->assertSameValues($expectedArguments, $command->getArguments());
    }

    /**
     * @group disconnected
     */
    public function testParseResponse(): void
    {
        $response = [[100, '1'], [200, '2'], [300, '3']];

        $this->assertSame($response, $this->getCommand()->parseResponse($response));
    }

    /**
     * @group connected
     * @return void
     * @requiresRedisVersion >= 8.9.0
     */
    public function testReadsAllSamplesAtOnce(): void
    {
        $redis = $this->getClient();

        $this->assertEquals('OK', $redis->tscreate('sensor:1'));
        $this->assertSame(100, $redis->tsadd('sensor:1', 100, 1.0));
        $this->assertSame(200, $redis->tsadd('sensor:1', 200, 2.0));
        $this->assertSame(300, $redis->tsadd('sensor:1', 300, 3.0));

        $this->assertEquals(
            [[100, '1'], [200, '2'], [300, '3']],
            $redis->tsread('sensor:1', 0)
        );
    }

    /**
     * @group connected
     * @return void
     * @requiresRedisVersion >= 8.9.0
     */
    public function testReadsSamplesUsingCursorPagination(): void
    {
        $redis = $this->getClient();

        $this->assertEquals('OK', $redis->tscreate('sensor:1'));
        $this->assertSame(100, $redis->tsadd('sensor:1', 100, 1.0));
        $this->assertSame(200, $redis->tsadd('sensor:1', 200, 2.0));
        $this->assertSame(300, $redis->tsadd('sensor:1', 300, 3.0));

        $arguments = (new ReadArguments())->maxCount(2);

        // First page returns the oldest max_count samples.
        $firstPage = $redis->tsread('sensor:1', 0, $arguments);
        $this->assertEquals([[100, '1'], [200, '2']], $firstPage);

        // Cursor advances past the last returned timestamp.
        $lastTimestamp = $firstPage[count($firstPage) - 1][0];
        $secondPage = $redis->tsread('sensor:1', $lastTimestamp + 1, $arguments);
        $this->assertEquals([[300, '3']], $secondPage);

        // Cursor past the newest sample returns an empty list.
        $lastTimestamp = $secondPage[count($secondPage) - 1][0];
        $this->assertEquals([], $redis->tsread('sensor:1', $lastTimestamp + 1, $arguments));
    }

    /**
     * @group connected
     * @return void
     * @requiresRedisVersion >= 8.9.0
     */
    public function testReadsSamplesUsingSpecialTimestampCursors(): void
    {
        $redis = $this->getClient();

        $this->assertEquals('OK', $redis->tscreate('sensor:1'));
        $this->assertSame(100, $redis->tsadd('sensor:1', 100, 1.0));
        $this->assertSame(200, $redis->tsadd('sensor:1', 200, 2.0));

        // "-" reads from the earliest sample.
        $this->assertEquals([[100, '1'], [200, '2']], $redis->tsread('sensor:1', '-'));

        // "+" is the latest sample's timestamp, inclusive.
        $this->assertEquals([[200, '2']], $redis->tsread('sensor:1', '+'));

        // "$" qualifies only samples added after the command, so without
        // blocking it returns an empty list immediately.
        $this->assertEquals([], $redis->tsread('sensor:1', '$'));
    }

    /**
     * @group connected
     * @return void
     * @requiresRedisVersion >= 8.9.0
     */
    public function testReadsMissingKeyReturnsEmptyListWithoutError(): void
    {
        $redis = $this->getClient();

        $this->assertEquals([], $redis->tsread('missing_key', 0));
    }

    /**
     * @group connected
     * @return void
     * @requiresRedisVersion >= 8.9.0
     */
    public function testBlocksForGivenTimeoutWhenNotEnoughSamplesAvailable(): void
    {
        $redis = $this->getClient();

        $this->assertEquals('OK', $redis->tscreate('sensor:1'));
        $this->assertSame(100, $redis->tsadd('sensor:1', 100, 1.0));
        $this->assertSame(200, $redis->tsadd('sensor:1', 200, 2.0));

        // Fewer qualifying samples than min_count: the command blocks until
        // the timeout elapses, then returns whatever qualifies.
        $start = microtime(true);
        $response = $redis->tsread('sensor:1', 0, (new ReadArguments())->block(100, 5));
        $elapsedMs = (microtime(true) - $start) * 1000;

        $this->assertGreaterThanOrEqual(100, $elapsedMs);
        $this->assertEquals([[100, '1'], [200, '2']], $response);
    }

    /**
     * @group connected
     * @return void
     * @requiresRedisVersion >= 8.9.0
     */
    public function testReturnsImmediatelyWhenMinCountSamplesAlreadyAvailable(): void
    {
        $redis = $this->getClient();

        $this->assertEquals('OK', $redis->tscreate('sensor:1'));
        $this->assertSame(100, $redis->tsadd('sensor:1', 100, 1.0));
        $this->assertSame(200, $redis->tsadd('sensor:1', 200, 2.0));

        // min_count is already satisfied, so BLOCK 0 (wait indefinitely)
        // returns immediately, capped by max_count.
        $response = $redis->tsread('sensor:1', 0, (new ReadArguments())->block(0, 2)->maxCount(2));

        $this->assertEquals([[100, '1'], [200, '2']], $response);
    }

    /**
     * @group connected
     * @return void
     * @requiresRedisVersion >= 8.9.0
     */
    public function testReadsAllSamplesAtOnceResp3(): void
    {
        $redis = $this->getResp3Client();

        $this->assertEquals('OK', $redis->tscreate('sensor:1'));
        $this->assertSame(100, $redis->tsadd('sensor:1', 100, 1.0));
        $this->assertSame(200, $redis->tsadd('sensor:1', 200, 2.0));

        $this->assertEquals(
            [[100, '1'], [200, '2']],
            $redis->tsread('sensor:1', 0)
        );
    }

    /**
     * @group connected
     * @dataProvider unexpectedValuesProvider
     * @param  ReadArguments $arguments
     * @param  string        $expectedExceptionMessage
     * @return void
     * @requiresRedisVersion >= 8.9.0
     */
    public function testThrowsExceptionOnCountConstraintViolations(
        ReadArguments $arguments,
        string $expectedExceptionMessage
    ): void {
        $redis = $this->getClient();

        $this->assertEquals('OK', $redis->tscreate('sensor:1'));
        $this->assertSame(100, $redis->tsadd('sensor:1', 100, 1.0));

        $this->expectException(ServerException::class);
        $this->expectExceptionMessage($expectedExceptionMessage);

        $redis->tsread('sensor:1', 0, $arguments);
    }

    public function argumentsProvider(): array
    {
        return [
            'with default arguments' => [
                ['key', 0],
                ['key', 0],
            ],
            'with special timestamp cursor' => [
                ['key', '$'],
                ['key', '$'],
            ],
            'with BLOCK modifier' => [
                ['key', 0, (new ReadArguments())->block(1000, 5)],
                ['key', 0, 'BLOCK', 1000, 5],
            ],
            'with MAX_COUNT modifier' => [
                ['key', 0, (new ReadArguments())->maxCount(10)],
                ['key', 0, 'MAX_COUNT', 10],
            ],
            'with all arguments' => [
                ['key', 0, (new ReadArguments())->block(1000, 5)->maxCount(10)],
                ['key', 0, 'BLOCK', 1000, 5, 'MAX_COUNT', 10],
            ],
        ];
    }

    public function unexpectedValuesProvider(): array
    {
        return [
            'with min_count greater than MAX_COUNT' => [
                (new ReadArguments())->block(10, 5)->maxCount(2),
                'BLOCK min_count must be <= MAX_COUNT',
            ],
            'with non-positive MAX_COUNT' => [
                (new ReadArguments())->maxCount(0),
                'MAX_COUNT must be a positive integer',
            ],
        ];
    }
}
