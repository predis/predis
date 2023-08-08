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
use Predis\Command\Argument\TimeSeries\InfoArguments;
use Predis\Command\Redis\PredisCommandTestCase;

/**
 * @group commands
 * @group realm-stack
 */
class TSINFO_Test extends PredisCommandTestCase
{
    /**
     * {@inheritDoc}
     */
    protected function getExpectedCommand(): string
    {
        return TSINFO::class;
    }

    /**
     * {@inheritDoc}
     */
    protected function getExpectedId(): string
    {
        return 'TSINFO';
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
    public function testReturnsInformationAboutGivenTimeSeries(): void
    {
        $redis = $this->getClient();
        $expectedResponse = ['totalSamples', 0, 'memoryUsage', 4239, 'firstTimestamp', 0, 'lastTimestamp', 0,
            'retentionTime', 60000, 'chunkCount', 1, 'chunkSize', 4096, 'chunkType', 'compressed', 'duplicatePolicy',
        'max', 'labels', [['sensor_id', '2'], ['area_id', '32']], 'sourceKey', null, 'rules', []];

        $arguments = (new CreateArguments())
            ->retentionMsecs(60000)
            ->duplicatePolicy(CommonArguments::POLICY_MAX)
            ->labels('sensor_id', 2, 'area_id', 32);

        $this->assertEquals(
            'OK',
            $redis->tscreate('temperature:2:32', $arguments)
        );

        $this->assertEquals($expectedResponse, $redis->tsinfo('temperature:2:32'));
    }

    /**
     * @group connected
     * @return void
     * @requiresRedisTimeSeriesVersion >= 1.10.0
     */
    public function testReturnsInformationAboutGivenTimeSeriesResp3(): void
    {
        $redis = $this->getResp3Client();
        $expectedResponse = ['totalSamples' => 0, 'memoryUsage' => 4239, 'firstTimestamp' => 0, 'lastTimestamp' => 0,
            'retentionTime' => 60000, 'chunkCount' => 1, 'chunkSize' => 4096, 'chunkType' => 'compressed',
            'duplicatePolicy' => 'max', 'labels' => ['sensor_id' => '2', 'area_id' => '32'], 'sourceKey' => null, 'rules' => []];

        $arguments = (new CreateArguments())
            ->retentionMsecs(60000)
            ->duplicatePolicy(CommonArguments::POLICY_MAX)
            ->labels('sensor_id', 2, 'area_id', 32);

        $this->assertEquals(
            'OK',
            $redis->tscreate('temperature:2:32', $arguments)
        );

        $this->assertEquals($expectedResponse, $redis->tsinfo('temperature:2:32'));
    }

    public function argumentsProvider(): array
    {
        return [
            'with default arguments' => [
                ['key'],
                ['key'],
            ],
            'with DEBUG modifier' => [
                ['key', (new InfoArguments())->debug()],
                ['key', 'DEBUG'],
            ],
        ];
    }
}
