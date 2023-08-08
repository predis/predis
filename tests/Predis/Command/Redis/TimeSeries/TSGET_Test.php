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

use Predis\Command\Argument\TimeSeries\CommonArguments;
use Predis\Command\Argument\TimeSeries\CreateArguments;
use Predis\Command\Argument\TimeSeries\GetArguments;
use Predis\Command\Redis\PredisCommandTestCase;
use Predis\Response\ServerException;

/**
 * @group commands
 * @group realm-stack
 */
class TSGET_Test extends PredisCommandTestCase
{
    /**
     * {@inheritDoc}
     */
    protected function getExpectedCommand(): string
    {
        return TSGET::class;
    }

    /**
     * {@inheritDoc}
     */
    protected function getExpectedId(): string
    {
        return 'TSGET';
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
    public function testGetSampleWithHighestTimestampFromGivenTimeSeries(): void
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
            123123123123,
            $redis->tsadd('temperature:2:32', 123123123123, 27)
        );

        $this->assertEquals(
            123123123124,
            $redis->tsadd('temperature:2:32', 123123123124, 28)
        );

        $this->assertEquals(
            123123123125,
            $redis->tsadd('temperature:2:32', 123123123125, 29)
        );

        $this->assertEquals([123123123125, '29'], $redis->tsget('temperature:2:32'));
    }

    /**
     * @group connected
     * @return void
     * @requiresRedisTimeSeriesVersion >= 1.0.0
     */
    public function testGetSampleWithHighestTimestampFromGivenTimeSeriesResp3(): void
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
            123123123123,
            $redis->tsadd('temperature:2:32', 123123123123, 27)
        );

        $this->assertEquals(
            123123123124,
            $redis->tsadd('temperature:2:32', 123123123124, 28)
        );

        $this->assertEquals(
            123123123125,
            $redis->tsadd('temperature:2:32', 123123123125, 29)
        );

        $this->assertEquals([123123123125, '29'], $redis->tsget('temperature:2:32'));
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

        $redis->tsget('non_existing_key');
    }

    public function argumentsProvider(): array
    {
        return [
            'with default arguments' => [
                ['key'],
                ['key'],
            ],
            'with LATEST modifier' => [
                ['key', (new GetArguments())->latest()],
                ['key', 'LATEST'],
            ],
        ];
    }
}
