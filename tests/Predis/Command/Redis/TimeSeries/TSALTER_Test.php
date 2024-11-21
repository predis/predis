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

use Predis\Command\Argument\TimeSeries\AlterArguments;
use Predis\Command\Argument\TimeSeries\CommonArguments;
use Predis\Command\Argument\TimeSeries\CreateArguments;
use Predis\Command\Redis\PredisCommandTestCase;
use Predis\Response\ServerException;
use UnexpectedValueException;

/**
 * @group commands
 * @group realm-stack
 */
class TSALTER_Test extends PredisCommandTestCase
{
    /**
     * {@inheritDoc}
     */
    protected function getExpectedCommand(): string
    {
        return TSALTER::class;
    }

    /**
     * {@inheritDoc}
     */
    protected function getExpectedId(): string
    {
        return 'TSALTER';
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
     * @return void
     */
    public function testFilterArgumentsThrowsExceptionOnNonPositiveValues(): void
    {
        $command = $this->getCommand();

        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('Ignore does not accept negative values');

        $command->setArguments(['key', 123123121321, 1.0, (new AlterArguments())->ignore(-2, -1)]);
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
    public function testAlterUpdatesExistingTimeSeriesConfiguration(): void
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

        $alterArguments = (new AlterArguments())
            ->retentionMsecs(10000000);

        $this->assertEquals(
            'OK',
            $redis->tsalter('temperature:2:32', $alterArguments)
        );
    }

    /**
     * @group connected
     * @group relay-resp3
     * @return void
     * @requiresRedisTimeSeriesVersion >= 1.12.01
     */
    public function testAlterUpdatesExistingTimeSeriesWithIgnoreOption(): void
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

        $alterArguments = (new AlterArguments())
            ->retentionMsecs(10000000)
            ->duplicatePolicy(CommonArguments::POLICY_LAST)
            ->ignore(10, 10);

        $this->assertEquals(
            'OK',
            $redis->tsalter('temperature:2:32', $alterArguments)
        );

        $this->assertEquals(
            1000,
            $redis->tsadd('temperature:2:32', 1000, 27)
        );

        $this->assertEquals(
            1000,
            $redis->tsadd('temperature:2:32', 1005, 27)
        );

        $this->assertEquals(
            1005,
            $redis->tsadd('temperature:2:32', 1005, 38)
        );
    }

    /**
     * @group connected
     * @return void
     * @requiresRedisTimeSeriesVersion >= 1.0.0
     */
    public function testThrowsExceptionOnNonExistingKey(): void
    {
        $redis = $this->getClient();

        $this->expectException(ServerException::class);
        $this->expectExceptionMessage('ERR TSDB: the key does not exist');

        $redis->tsalter('non_existing_key');
    }

    public function argumentsProvider(): array
    {
        return [
            'with default arguments' => [
                ['key'],
                ['key'],
            ],
            'with RETENTION modifier' => [
                ['key', (new AlterArguments())->retentionMsecs(100)],
                ['key', 'RETENTION', 100],
            ],
            'with CHUNK_SIZE modifier' => [
                ['key', (new AlterArguments())->chunkSize(100)],
                ['key', 'CHUNK_SIZE', 100],
            ],
            'with DUPLICATE_POLICY modifier' => [
                ['key', (new AlterArguments())->duplicatePolicy(CommonArguments::POLICY_FIRST)],
                ['key', 'DUPLICATE_POLICY', CommonArguments::POLICY_FIRST],
            ],
            'with IGNORE modifier' => [
                ['key', (new AlterArguments())->ignore(10, 1.1)],
                ['key', 'IGNORE', 10, 1.1],
            ],
            'with all modifiers' => [
                ['key', (new AlterArguments())->retentionMsecs(100)->chunkSize(100)->duplicatePolicy(CommonArguments::POLICY_FIRST)],
                ['key', 'RETENTION', 100, 'CHUNK_SIZE', 100, 'DUPLICATE_POLICY', CommonArguments::POLICY_FIRST],
            ],
        ];
    }
}
