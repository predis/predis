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

use Predis\Command\Argument\TimeSeries\CommonArguments;
use Predis\Command\Argument\TimeSeries\CreateArguments;
use Predis\Command\PrefixableCommand;
use Predis\Command\Redis\PredisCommandTestCase;
use Predis\Response\ServerException;

/**
 * @group commands
 * @group realm-stack
 */
class TSMADD_Test extends PredisCommandTestCase
{
    /**
     * {@inheritDoc}
     */
    protected function getExpectedCommand(): string
    {
        return TSMADD::class;
    }

    /**
     * {@inheritDoc}
     */
    protected function getExpectedId(): string
    {
        return 'TSMADD';
    }

    /**
     * @group disconnected
     */
    public function testFilterArguments(): void
    {
        $actualArguments = ['key1', 1000, 1001, 'key2', 1000, 1001];
        $expectedArguments = ['key1', 1000, 1001, 'key2', 1000, 1001];

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
     */
    public function testPrefixKeys(): void
    {
        /** @var PrefixableCommand $command */
        $command = $this->getCommand();
        $actualArguments = ['key', 1000, 'value', 'key1', 1001, 'value1'];
        $prefix = 'prefix:';
        $expectedArguments = ['prefix:key', 1000, 'value', 'prefix:key1', 1001, 'value1'];

        $command->setArguments($actualArguments);
        $command->prefixKeys($prefix);

        $this->assertSame($expectedArguments, $command->getArguments());
    }

    /**
     * @group connected
     * @group relay-resp3
     * @return void
     * @requiresRedisTimeSeriesVersion >= 1.0.0
     */
    public function testAddSamplesIntoFewTimeSeries(): void
    {
        $redis = $this->getClient();

        $createArguments = (new CreateArguments())
            ->retentionMsecs(60000)
            ->duplicatePolicy(CommonArguments::POLICY_MAX)
            ->labels('sensor_id', 2, 'area_id', 32);

        $this->assertEquals(
            'OK',
            $redis->tscreate('temperature:2:32', $createArguments)
        );

        $this->assertEquals(
            'OK',
            $redis->tscreate('temperature:2:33', $createArguments)
        );

        $this->assertEquals(
            [123123123123, 123123123124],
            $redis->tsmadd('temperature:2:32', 123123123123, 27, 'temperature:2:33', 123123123124, 28)
        );
    }

    /**
     * @group connected
     * @return void
     * @requiresRedisTimeSeriesVersion >= 1.0.0
     */
    public function testAddSamplesIntoFewTimeSeriesResp3(): void
    {
        $redis = $this->getResp3Client();

        $createArguments = (new CreateArguments())
            ->retentionMsecs(60000)
            ->duplicatePolicy(CommonArguments::POLICY_MAX)
            ->labels('sensor_id', 2, 'area_id', 32);

        $this->assertEquals(
            'OK',
            $redis->tscreate('temperature:2:32', $createArguments)
        );

        $this->assertEquals(
            'OK',
            $redis->tscreate('temperature:2:33', $createArguments)
        );

        $this->assertEquals(
            [123123123123, 123123123124],
            $redis->tsmadd('temperature:2:32', 123123123123, 27, 'temperature:2:33', 123123123124, 28)
        );
    }

    /**
     * @group connected
     * @group relay-resp3
     * @return void
     * @requiresRedisTimeSeriesVersion >= 1.0.0
     */
    public function testThrowsExceptionOnNonWrongArgumentsNumber(): void
    {
        $redis = $this->getClient();

        $this->expectException(ServerException::class);

        $redis->tsmadd('temperature:2:32', 123123123123, 27, 'temperature:2:33', 123123123124);
    }
}
