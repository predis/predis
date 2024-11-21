<?php

/*
 * This file is part of the Predis package.
 *
 * (c) 2009-2020 Daniele Alessandri
 * (c) 2021-2024 Till Krüss
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Command\Redis\TimeSeries;

use Predis\Command\Argument\TimeSeries\CreateArguments;
use Predis\Command\Redis\PredisCommandTestCase;
use Predis\Response\ServerException;

/**
 * @group commands
 * @group realm-stack
 */
class TSCREATERULE_Test extends PredisCommandTestCase
{
    /**
     * {@inheritDoc}
     */
    protected function getExpectedCommand(): string
    {
        return TSCREATERULE::class;
    }

    /**
     * {@inheritDoc}
     */
    protected function getExpectedId(): string
    {
        return 'TSCREATERULE';
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
     * @group relay-resp3
     * @return void
     * @requiresRedisTimeSeriesVersion >= 1.0.0
     */
    public function testCreateCompactionRuleWithinGivenTimeSeries(): void
    {
        $redis = $this->getClient();

        $arguments = (new CreateArguments())
            ->labels('type', 'temp', 'location', 'TLV');

        $this->assertEquals('OK', $redis->tscreate('temp:TLV', $arguments));
        $this->assertEquals('OK', $redis->tscreate('dailyAvgTemp:TLV', $arguments));
        $this->assertEquals(
            'OK',
            $redis->tscreaterule('temp:TLV', 'dailyAvgTemp:TLV', 'twa', 86400000)
        );
    }

    /**
     * @group connected
     * @return void
     * @requiresRedisTimeSeriesVersion >= 1.0.0
     */
    public function testThrowsExceptionOnNonExistingDestinationKey(): void
    {
        $redis = $this->getClient();

        $this->expectException(ServerException::class);
        $this->expectExceptionMessage('ERR TSDB: the key does not exist');

        $redis->tscreaterule('temp:TLV', 'dailyAvgTemp:TLV', 'twa', 86400000);
    }

    /**
     * @group connected
     * @group relay-resp3
     * @return void
     * @requiresRedisTimeSeriesVersion >= 1.0.0
     */
    public function testThrowsExceptionOnWrongAggregationTypeGiven(): void
    {
        $redis = $this->getClient();

        $arguments = (new CreateArguments())
            ->labels('type', 'temp', 'location', 'TLV');

        $this->assertEquals('OK', $redis->tscreate('temp:TLV', $arguments));
        $this->assertEquals('OK', $redis->tscreate('dailyAvgTemp:TLV', $arguments));

        $this->expectException(ServerException::class);
        $this->expectExceptionMessage('ERR TSDB: Unknown aggregation type');

        $redis->tscreaterule('temp:TLV', 'dailyAvgTemp:TLV', 'wrong', 86400000);
    }

    public function argumentsProvider(): array
    {
        return [
            'with default arguments' => [
                ['sourceKey', 'destKey', 'sum', 100000],
                ['sourceKey', 'destKey', 'AGGREGATION', 'sum', 100000],
            ],
            'with alignTimestamp argument' => [
                ['sourceKey', 'destKey', 'sum', 100000, 10000000],
                ['sourceKey', 'destKey', 'AGGREGATION', 'sum', 100000, 10000000],
            ],
        ];
    }
}
