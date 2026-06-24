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

use Predis\Command\Argument\TimeSeries\CreateArguments;
use Predis\Command\Argument\TimeSeries\NRangeArguments;
use Predis\Command\PrefixableCommand;
use Predis\Command\Redis\PredisCommandTestCase;
use Predis\Response\ServerException;

/**
 * @group commands
 * @group realm-stack
 */
class TSNREVRANGE_Test extends PredisCommandTestCase
{
    /**
     * {@inheritDoc}
     */
    protected function getExpectedCommand(): string
    {
        return TSNREVRANGE::class;
    }

    /**
     * {@inheritDoc}
     */
    protected function getExpectedId(): string
    {
        return 'TSNREVRANGE';
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
        $this->assertSame(1, $this->getCommand()->parseResponse(1));
    }

    /**
     * @group disconnected
     * @dataProvider parseResponseProvider
     */
    public function testParseResponsePassesThroughTimestampMajorResults(array $response): void
    {
        $this->assertSame($response, $this->getCommand()->parseResponse($response));
    }

    public function parseResponseProvider(): array
    {
        return [
            'single key' => [
                [[1020, ['120']], [1000, ['100']]],
            ],
            'multiple keys' => [
                [[1020, ['120', '170']], [1000, ['100', '200']]],
            ],
        ];
    }

    /**
     * @group disconnected
     */
    public function testPrefixKeys(): void
    {
        /** @var PrefixableCommand $command */
        $command = $this->getCommand();
        $actualArguments = [['key1', 'key2'], 1000, 1001, (new NRangeArguments())->count(100)];
        $prefix = 'prefix:';
        $expectedArguments = [2, 'prefix:key1', 'prefix:key2', 1000, 1001, 'COUNT', 100];

        $command->setArguments($actualArguments);
        $command->prefixKeys($prefix);

        $this->assertSame($expectedArguments, $command->getArguments());
    }

    /**
     * @group connected
     * @group relay-resp3
     * @return void
     * @requiresRedisVersion >= 8.9.0
     */
    public function testReturnsQueriedRangeForMultipleKeysInReverseDirection(): void
    {
        $redis = $this->getClient();

        $this->assertEquals('OK', $redis->tscreate('temp:TLV', (new CreateArguments())->labels('type', 'temp', 'location', 'TLV')));
        $this->assertEquals('OK', $redis->tscreate('temp:JLM', (new CreateArguments())->labels('type', 'temp', 'location', 'JLM')));

        $this->assertSame([1000, 1010, 1020], $redis->tsmadd('temp:TLV', 1000, 30, 'temp:TLV', 1010, 35, 'temp:TLV', 1020, 40));
        $this->assertSame([1000, 1010, 1020], $redis->tsmadd('temp:JLM', 1000, 25, 'temp:JLM', 1010, 27, 'temp:JLM', 1020, 29));

        $this->assertEquals(
            [
                [1020, ['40', '29']],
                [1010, ['35', '27']],
                [1000, ['30', '25']],
            ],
            $redis->tsnrevrange(['temp:TLV', 'temp:JLM'], '-', '+')
        );
    }

    /**
     * @group connected
     * @return void
     * @requiresRedisVersion >= 8.9.0
     */
    public function testReturnsQueriedRangeForMultipleKeysInReverseDirectionResp3(): void
    {
        $redis = $this->getResp3Client();

        $this->assertEquals('OK', $redis->tscreate('temp:TLV', (new CreateArguments())->labels('type', 'temp', 'location', 'TLV')));
        $this->assertEquals('OK', $redis->tscreate('temp:JLM', (new CreateArguments())->labels('type', 'temp', 'location', 'JLM')));

        $this->assertSame([1000, 1010, 1020], $redis->tsmadd('temp:TLV', 1000, 30, 'temp:TLV', 1010, 35, 'temp:TLV', 1020, 40));
        $this->assertSame([1000, 1010, 1020], $redis->tsmadd('temp:JLM', 1000, 25, 'temp:JLM', 1010, 27, 'temp:JLM', 1020, 29));

        $this->assertEquals(
            [
                [1020, ['40', '29']],
                [1010, ['35', '27']],
                [1000, ['30', '25']],
            ],
            $redis->tsnrevrange(['temp:TLV', 'temp:JLM'], '-', '+')
        );
    }

    /**
     * @group connected
     * @group relay-resp3
     * @return void
     * @requiresRedisVersion >= 8.9.0
     */
    public function testPreservesInputKeyOrder(): void
    {
        $redis = $this->getClient();

        $this->assertEquals('OK', $redis->tscreate('temp:TLV', (new CreateArguments())->labels('type', 'temp', 'location', 'TLV')));
        $this->assertEquals('OK', $redis->tscreate('temp:JLM', (new CreateArguments())->labels('type', 'temp', 'location', 'JLM')));

        $this->assertSame([1000], $redis->tsmadd('temp:TLV', 1000, 30));
        $this->assertSame([1000], $redis->tsmadd('temp:JLM', 1000, 25));

        // Reversed key order should reverse the values in each row.
        $this->assertEquals(
            [[1000, ['25', '30']]],
            $redis->tsnrevrange(['temp:JLM', 'temp:TLV'], '-', '+')
        );
    }

    /**
     * @group connected
     * @group relay-resp3
     * @return void
     * @requiresRedisVersion >= 8.9.0
     */
    public function testReturnsRangeLimitedByCount(): void
    {
        $redis = $this->getClient();

        $this->assertEquals('OK', $redis->tscreate('temp:TLV', (new CreateArguments())->labels('type', 'temp', 'location', 'TLV')));
        $this->assertEquals('OK', $redis->tscreate('temp:JLM', (new CreateArguments())->labels('type', 'temp', 'location', 'JLM')));

        $this->assertSame([1000, 1010, 1020], $redis->tsmadd('temp:TLV', 1000, 30, 'temp:TLV', 1010, 35, 'temp:TLV', 1020, 40));
        $this->assertSame([1000, 1010, 1020], $redis->tsmadd('temp:JLM', 1000, 25, 'temp:JLM', 1010, 27, 'temp:JLM', 1020, 29));

        $arguments = (new NRangeArguments())->count(2);

        // In reverse direction COUNT keeps the rows with the highest timestamps.
        $this->assertEquals(
            [
                [1020, ['40', '29']],
                [1010, ['35', '27']],
            ],
            $redis->tsnrevrange(['temp:TLV', 'temp:JLM'], '-', '+', $arguments)
        );
    }

    /**
     * @group connected
     * @group relay-resp3
     * @return void
     * @requiresRedisVersion >= 8.9.0
     */
    public function testReturnsRangeWithAggregationPerKey(): void
    {
        $redis = $this->getClient();

        $this->assertEquals('OK', $redis->tscreate('stock:A', (new CreateArguments())->labels('type', 'stock', 'name', 'A')));
        $this->assertEquals('OK', $redis->tscreate('stock:B', (new CreateArguments())->labels('type', 'stock', 'name', 'B')));

        $this->assertSame([1000, 1010, 1020], $redis->tsmadd('stock:A', 1000, 100, 'stock:A', 1010, 110, 'stock:A', 1020, 120));
        $this->assertSame([1000, 1010, 1020], $redis->tsmadd('stock:B', 1000, 200, 'stock:B', 1010, 210, 'stock:B', 1020, 220));

        // Exactly numkeys aggregators are required, one per key.
        $arguments = (new NRangeArguments())->aggregation([NRangeArguments::AGG_MIN, NRangeArguments::AGG_MAX], 1000);

        $response = $redis->tsnrevrange(['stock:A', 'stock:B'], '-', '+', $arguments);

        // Response is timestamp-major and each row holds one aggregated value
        // per queried key, preserving the input key order.
        $this->assertNotEmpty($response);

        foreach ($response as $row) {
            $this->assertIsInt($row[0]);
            $this->assertCount(2, $row[1]);
        }
    }

    /**
     * @group connected
     * @group relay-resp3
     * @return void
     * @requiresRedisVersion >= 8.9.0
     */
    public function testThrowsExceptionOnNonExistingKey(): void
    {
        $redis = $this->getClient();

        $this->expectException(ServerException::class);
        $this->expectExceptionMessage('ERR TSDB: the key does not exist');

        $redis->tsnrevrange(['non_existing_key'], 1000, 1000);
    }

    public function argumentsProvider(): array
    {
        return [
            'with single key' => [
                [['key'], 10000, 10001],
                [1, 'key', 10000, 10001],
            ],
            'with multiple keys' => [
                [['key1', 'key2'], 10000, 10001],
                [2, 'key1', 'key2', 10000, 10001],
            ],
            'with duplicate keys preserved' => [
                [['key', 'key'], 10000, 10001],
                [2, 'key', 'key', 10000, 10001],
            ],
            'with LATEST modifier' => [
                [['key'], 10000, 10001, (new NRangeArguments())->latest()],
                [1, 'key', 10000, 10001, 'LATEST'],
            ],
            'with FILTER_BY_TS modifier' => [
                [['key'], 10000, 10001, (new NRangeArguments())->filterByTs(1000, 1001)],
                [1, 'key', 10000, 10001, 'FILTER_BY_TS', 1000, 1001],
            ],
            'with FILTER_BY_VALUE modifier' => [
                [['key'], 10000, 10001, (new NRangeArguments())->filterByValue(1000, 1001)],
                [1, 'key', 10000, 10001, 'FILTER_BY_VALUE', 1000, 1001],
            ],
            'with COUNT modifier' => [
                [['key'], 10000, 10001, (new NRangeArguments())->count(100)],
                [1, 'key', 10000, 10001, 'COUNT', 100],
            ],
            'with AGGREGATION modifier - default arguments' => [
                [['key1', 'key2'], 10000, 10001, (new NRangeArguments())->aggregation(['min', 'max'], 100)],
                [2, 'key1', 'key2', 10000, 10001, 'AGGREGATION', 'min', 'max', 100],
            ],
            'with AGGREGATION modifier - with ALIGN' => [
                [['key'], 10000, 10001, (new NRangeArguments())->aggregation('sum', 100, 100)],
                [1, 'key', 10000, 10001, 'ALIGN', 100, 'AGGREGATION', 'sum', 100],
            ],
            'with AGGREGATION modifier - with BUCKETTIMESTAMP' => [
                [['key'], 10000, 10001, (new NRangeArguments())->aggregation('sum', 100, 0, 1000)],
                [1, 'key', 10000, 10001, 'AGGREGATION', 'sum', 100, 'BUCKETTIMESTAMP', 1000],
            ],
            'with AGGREGATION modifier - with EMPTY' => [
                [['key'], 10000, 10001, (new NRangeArguments())->aggregation('sum', 100, 0, 0, true)],
                [1, 'key', 10000, 10001, 'AGGREGATION', 'sum', 100, 'EMPTY'],
            ],
            'with all modifiers' => [
                [['key1', 'key2'], 10000, 10001, (new NRangeArguments())->latest()->filterByTs(1000, 1001)->filterByValue(1000, 1001)->count(100)->aggregation(['min', 'max'], 100)],
                [2, 'key1', 'key2', 10000, 10001, 'LATEST', 'FILTER_BY_TS', 1000, 1001, 'FILTER_BY_VALUE', 1000, 1001, 'COUNT', 100, 'AGGREGATION', 'min', 'max', 100],
            ],
        ];
    }
}
