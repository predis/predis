<?php

/*
 * This file is part of the Predis package.
 *
 * (c) 2009-2020 Daniele Alessandri
 * (c) 2021-2023 Till Krüss
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Command\Redis\TimeSeries;

use Predis\Command\Argument\TimeSeries\CreateArguments;
use Predis\Command\Argument\TimeSeries\RangeArguments;
use Predis\Command\Redis\PredisCommandTestCase;
use Predis\Response\ServerException;

/**
 * @group commands
 * @group realm-stack
 */
class TSRANGE_Test extends PredisCommandTestCase
{
    /**
     * {@inheritDoc}
     */
    protected function getExpectedCommand(): string
    {
        return TSRANGE::class;
    }

    /**
     * {@inheritDoc}
     */
    protected function getExpectedId(): string
    {
        return 'TSRANGE';
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
     * @group connected
     * @group relay-incompatible
     * @return void
     * @requiresRedisTimeSeriesVersion >= 1.0.0
     */
    public function testReturnsQueriedRangeInForwardDirectionFromGivenTimeSeries(): void
    {
        $redis = $this->getClient();

        $createArguments = (new CreateArguments())->labels('type', 'temp', 'location', 'TLV');
        $this->assertEquals('OK', $redis->tscreate('temp:TLV', $createArguments));

        $this->assertSame(
            [1000, 1010, 1020, 1030],
            $redis->tsmadd('temp:TLV', 1000, 30, 'temp:TLV', 1010, 35, 'temp:TLV', 1020, 9999, 'temp:TLV', 1030, 40)
        );

        $rangeArguments = (new RangeArguments())->filterByValue(-100, 100);
        $this->assertEquals(
            [[1000, '30'], [1010, '35'], [1030, '40']],
            $redis->tsrange('temp:TLV', '-', '+', $rangeArguments)
        );
    }

    /**
     * @group connected
     * @return void
     * @requiresRedisTimeSeriesVersion >= 1.0.0
     */
    public function testReturnsQueriedRangeInForwardDirectionFromGivenTimeSeriesResp3(): void
    {
        $redis = $this->getResp3Client();

        $createArguments = (new CreateArguments())->labels('type', 'temp', 'location', 'TLV');
        $this->assertEquals('OK', $redis->tscreate('temp:TLV', $createArguments));

        $this->assertSame(
            [1000, 1010, 1020, 1030],
            $redis->tsmadd('temp:TLV', 1000, 30, 'temp:TLV', 1010, 35, 'temp:TLV', 1020, 9999, 'temp:TLV', 1030, 40)
        );

        $rangeArguments = (new RangeArguments())->filterByValue(-100, 100);
        $this->assertEquals(
            [[1000, '30'], [1010, '35'], [1030, '40']],
            $redis->tsrange('temp:TLV', '-', '+', $rangeArguments)
        );
    }

    /**
     * @group connected
     * @group relay-incompatible
     * @return void
     * @requiresRedisTimeSeriesVersion >= 1.0.0
     */
    public function testThrowsExceptionOnNonExistingKey(): void
    {
        $redis = $this->getClient();

        $this->expectException(ServerException::class);
        $this->expectExceptionMessage('ERR TSDB: the key does not exist');

        $redis->tsrange('non_existing_key', 1000, 1000);
    }

    public function argumentsProvider(): array
    {
        return [
            'with default arguments' => [
                ['key', 10000, 10001],
                ['key', 10000, 10001],
            ],
            'with LATEST modifier' => [
                ['key', 10000, 10001, (new RangeArguments())->latest()],
                ['key', 10000, 10001, 'LATEST'],
            ],
            'with FILTER_BY_TS modifier' => [
                ['key', 10000, 10001, (new RangeArguments())->filterByTs(1000, 1001)],
                ['key', 10000, 10001, 'FILTER_BY_TS', 1000, 1001],
            ],
            'with FILTER_BY_VALUE modifier' => [
                ['key', 10000, 10001, (new RangeArguments())->filterByValue(1000, 1001)],
                ['key', 10000, 10001, 'FILTER_BY_VALUE', 1000, 1001],
            ],
            'with COUNT modifier' => [
                ['key', 10000, 10001, (new RangeArguments())->count(100)],
                ['key', 10000, 10001, 'COUNT', 100],
            ],
            'with AGGREGATION modifier - default arguments' => [
                ['key', 10000, 10001, (new RangeArguments())->aggregation('sum', 100)],
                ['key', 10000, 10001, 'AGGREGATION', 'sum', 100],
            ],
            'with AGGREGATION modifier - with ALIGN' => [
                ['key', 10000, 10001, (new RangeArguments())->aggregation('sum', 100, 100)],
                ['key', 10000, 10001, 'ALIGN', 100, 'AGGREGATION', 'sum', 100],
            ],
            'with AGGREGATION modifier - with BUCKETTIMESTAMP' => [
                ['key', 10000, 10001, (new RangeArguments())->aggregation('sum', 100, 0, 1000)],
                ['key', 10000, 10001, 'AGGREGATION', 'sum', 100, 'BUCKETTIMESTAMP', 1000],
            ],
            'with AGGREGATION modifier - with EMPTY' => [
                ['key', 10000, 10001, (new RangeArguments())->aggregation('sum', 100, 0, 0, true)],
                ['key', 10000, 10001, 'AGGREGATION', 'sum', 100, 'EMPTY'],
            ],
            'with all modifiers' => [
                ['key', 10000, 10001, (new RangeArguments())->latest()->filterByTs(1000, 1001)->filterByValue(1000, 1001)->count(100)->aggregation('sum', 100)],
                ['key', 10000, 10001, 'LATEST', 'FILTER_BY_TS', 1000, 1001, 'FILTER_BY_VALUE', 1000, 1001, 'COUNT', 100, 'AGGREGATION', 'sum', 100],
            ],
        ];
    }
}
