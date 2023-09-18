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

use Predis\Command\Argument\TimeSeries\AddArguments;
use Predis\Command\Argument\TimeSeries\CommonArguments;
use Predis\Command\Argument\TimeSeries\CreateArguments;
use Predis\Command\Argument\TimeSeries\DecrByArguments;
use Predis\Command\Redis\PredisCommandTestCase;
use Predis\Response\ServerException;

/**
 * @group commands
 * @group realm-stack
 */
class TSDECRBY_Test extends PredisCommandTestCase
{
    /**
     * {@inheritDoc}
     */
    protected function getExpectedCommand(): string
    {
        return TSDECRBY::class;
    }

    /**
     * {@inheritDoc}
     */
    protected function getExpectedId(): string
    {
        return 'TSDECRBY';
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
    public function testDecrByDecreasesValueAndTimestampOfExistingSample(): void
    {
        $redis = $this->getClient();

        $arguments = (new CreateArguments())
            ->retentionMsecs(60000)
            ->duplicatePolicy(CommonArguments::POLICY_MAX)
            ->labels('sensor_id', 2, 'area_id', 32);

        $this->assertEquals(
            'OK',
            $redis->tscreate('temperature:2:32', $arguments)
        );

        $addArguments = (new AddArguments())
            ->retentionMsecs(31536000000);

        $this->assertEquals(
            123123123123,
            $redis->tsadd('temperature:2:32', 123123123123, 27, $addArguments)
        );

        $this->assertEquals(
            123123123124,
            $redis->tsdecrby('temperature:2:32', 1, (new DecrByArguments())->timestamp(123123123124))
        );
    }

    /**
     * @group connected
     * @return void
     * @requiresRedisTimeSeriesVersion >= 1.0.0
     */
    public function testDecrByCreateNewSampleIfNotExists(): void
    {
        $redis = $this->getClient();

        $arguments = (new CreateArguments())
            ->retentionMsecs(60000)
            ->duplicatePolicy(CommonArguments::POLICY_MAX)
            ->labels('sensor_id', 2, 'area_id', 32);

        $this->assertEquals(
            'OK',
            $redis->tscreate('temperature:2:32', $arguments)
        );

        $this->assertEquals(
            123123123123,
            $redis->tsdecrby('temperature:2:32', 27, (new DecrByArguments())->timestamp(123123123123))
        );
    }

    /**
     * @group connected
     * @group relay-resp3
     * @return void
     * @requiresRedisTimeSeriesVersion >= 1.0.0
     */
    public function testThrowsExceptionOnOlderTimestampGiven(): void
    {
        $redis = $this->getClient();

        $arguments = (new CreateArguments())
            ->retentionMsecs(60000)
            ->duplicatePolicy(CommonArguments::POLICY_MAX)
            ->labels('sensor_id', 2, 'area_id', 32);

        $this->assertEquals(
            'OK',
            $redis->tscreate('temperature:2:32', $arguments)
        );

        $addArguments = (new AddArguments())
            ->retentionMsecs(31536000000);

        $this->assertEquals(
            123123123123,
            $redis->tsadd('temperature:2:32', 123123123123, 27, $addArguments)
        );

        $this->expectException(ServerException::class);

        $redis->tsdecrby('temperature:2:32', 27, (new DecrByArguments())->timestamp(123123123122));
    }

    public function argumentsProvider(): array
    {
        return [
            'with default arguments' => [
                ['key', 1.0],
                ['key', 1.0],
            ],
            'with TIMESTAMP modifier' => [
                ['key', 1.0, (new DecrByArguments())->timestamp(10)],
                ['key', 1.0, 'TIMESTAMP', 10],
            ],
            'with RETENTION modifier' => [
                ['key', 1.0, (new DecrByArguments())->retentionMsecs(100)],
                ['key', 1.0, 'RETENTION', 100],
            ],
            'with UNCOMPRESSED modifier' => [
                ['key', 1.0, (new DecrByArguments())->uncompressed()],
                ['key', 1.0, 'UNCOMPRESSED'],
            ],
            'with CHUNK_SIZE modifier' => [
                ['key', 1.0, (new DecrByArguments())->chunkSize(100)],
                ['key', 1.0, 'CHUNK_SIZE', 100],
            ],
            'with LABELS modifier' => [
                ['key', 1.0, (new DecrByArguments())->labels('label1', 1, 'label2', 2)],
                ['key', 1.0, 'LABELS', 'label1', 1, 'label2', 2],
            ],
            'with all modifiers' => [
                ['key', 1.0, (new DecrByArguments())->timestamp(10)->retentionMsecs(100)->uncompressed()->chunkSize(100)->labels('label1', 1, 'label2', 2)],
                ['key', 1.0, 'TIMESTAMP', 10, 'RETENTION', 100, 'UNCOMPRESSED', 'CHUNK_SIZE', 100, 'LABELS', 'label1', 1, 'label2', 2],
            ],
        ];
    }
}
