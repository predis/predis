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
use Predis\Command\Redis\PredisCommandTestCase;
use Predis\Response\ServerException;

/**
 * @group commands
 * @group realm-stack
 */
class TSDELETERULE_Test extends PredisCommandTestCase
{
    /**
     * {@inheritDoc}
     */
    protected function getExpectedCommand(): string
    {
        return TSDELETERULE::class;
    }

    /**
     * {@inheritDoc}
     */
    protected function getExpectedId(): string
    {
        return 'TSDELETERULE';
    }

    /**
     * @group disconnected
     */
    public function testFilterArguments(): void
    {
        $actualArguments = ['sourceKey', 'destKey'];
        $expectedArguments = ['sourceKey', 'destKey'];

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
    public function testDeleteCompactionRuleFromGivenTimeSeries(): void
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
        $this->assertEquals(
            'OK',
            $redis->tsdeleterule('temp:TLV', 'dailyAvgTemp:TLV')
        );
    }

    /**
     * @group connected
     * @return void
     * @requiresRedisTimeSeriesVersion >= 1.0.0
     */
    public function testDeleteCompactionRuleFromGivenTimeSeriesResp3(): void
    {
        $redis = $this->getResp3Client();

        $arguments = (new CreateArguments())
            ->labels('type', 'temp', 'location', 'TLV');

        $this->assertEquals('OK', $redis->tscreate('temp:TLV', $arguments));
        $this->assertEquals('OK', $redis->tscreate('dailyAvgTemp:TLV', $arguments));
        $this->assertEquals(
            'OK',
            $redis->tscreaterule('temp:TLV', 'dailyAvgTemp:TLV', 'twa', 86400000)
        );
        $this->assertEquals(
            'OK',
            $redis->tsdeleterule('temp:TLV', 'dailyAvgTemp:TLV')
        );
    }

    /**
     * @group connected
     * @group relay-incompatible
     * @return void
     * @requiresRedisTimeSeriesVersion >= 1.0.0
     */
    public function testThrowsExceptionOnNonExistingDestinationKey(): void
    {
        $redis = $this->getClient();

        $this->expectException(ServerException::class);
        $this->expectExceptionMessage('ERR TSDB: the key does not exist');

        $redis->tsdeleterule('temp:TLV', 'dailyAvgTemp:TLV');
    }
}
