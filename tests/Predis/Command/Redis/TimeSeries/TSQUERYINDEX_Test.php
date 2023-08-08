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
class TSQUERYINDEX_Test extends PredisCommandTestCase
{
    /**
     * {@inheritDoc}
     */
    protected function getExpectedCommand(): string
    {
        return TSQUERYINDEX::class;
    }

    /**
     * {@inheritDoc}
     */
    protected function getExpectedId(): string
    {
        return 'TSQUERYINDEX';
    }

    /**
     * @group disconnected
     */
    public function testFilterArguments(): void
    {
        $actualArguments = ["role='test'"];
        $expectedArguments = ["role='test'"];

        $command = $this->getCommand();
        $command->setArguments($actualArguments);

        $this->assertSameValues($expectedArguments, $command->getArguments());
    }

    /**
     * @group connected
     * @group relay-incompatible
     * @return void
     * @requiresRedisTimeSeriesVersion >= 1.0.0
     */
    public function testQueryReturnsKeysMatchingGivenFilterExpression(): void
    {
        $redis = $this->getClient();

        $arguments = (new CreateArguments())
            ->labels('type', 'temp', 'location', 'TLV');

        $this->assertEquals('OK', $redis->tscreate('temp:TLV', $arguments));

        $anotherArguments = (new CreateArguments())
            ->labels('type', 'temp', 'location', 'JER');

        $this->assertEquals('OK', $redis->tscreate('temp:JER', $anotherArguments));

        $this->assertSame(['temp:TLV'], $redis->tsqueryindex('location=TLV'));
    }

    /**
     * @group connected
     * @return void
     * @requiresRedisTimeSeriesVersion >= 1.0.0
     */
    public function testQueryReturnsKeysMatchingGivenFilterExpressionResp3(): void
    {
        $redis = $this->getResp3Client();

        $arguments = (new CreateArguments())
            ->labels('type', 'temp', 'location', 'TLV');

        $this->assertEquals('OK', $redis->tscreate('temp:TLV', $arguments));

        $anotherArguments = (new CreateArguments())
            ->labels('type', 'temp', 'location', 'JER');

        $this->assertEquals('OK', $redis->tscreate('temp:JER', $anotherArguments));

        $this->assertSame(['temp:TLV'], $redis->tsqueryindex('location=TLV'));
    }

    /**
     * @group connected
     * @group relay-incompatible
     * @return void
     * @requiresRedisTimeSeriesVersion >= 1.0.0
     */
    public function testThrowsExceptionOnInvalidFilterExpression(): void
    {
        $redis = $this->getClient();

        $this->expectException(ServerException::class);
        $this->expectExceptionMessage('ERR TSDB: failed parsing labels');

        $redis->tsqueryindex('wrong');
    }
}
