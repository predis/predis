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

use Predis\Command\Argument\TimeSeries\CommonArguments;
use Predis\Command\Argument\TimeSeries\CreateArguments;
use Predis\Command\Redis\PredisCommandTestCase;
use Predis\Response\ServerException;
use UnexpectedValueException;

/**
 * @group commands
 * @group realm-stack
 */
class TSCREATE_Test extends PredisCommandTestCase
{
    /**
     * {@inheritDoc}
     */
    protected function getExpectedCommand(): string
    {
        return TSCREATE::class;
    }

    /**
     * {@inheritDoc}
     */
    protected function getExpectedId(): string
    {
        return 'TSCREATE';
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

        $command->setArguments(['key', 123123121321, 1.0, (new CreateArguments())->ignore(-2, -1)]);
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
    public function testCreatesTimeSeriesWithGivenArguments(): void
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
    }

    /**
     * @group connected
     * @group relay-resp3
     * @return void
     * @requiresRedisTimeSeriesVersion >= 1.12.01
     */
    public function testCreatesTimeSeriesWithIgnoreOption(): void
    {
        $redis = $this->getClient();

        $arguments = (new CreateArguments())
            ->retentionMsecs(60000)
            ->duplicatePolicy(CommonArguments::POLICY_LAST)
            ->labels('sensor_id', 2, 'area_id', 32)
            ->ignore(10, 10);

        $this->assertEquals(
            'OK',
            $redis->tscreate('temperature:2:32', $arguments)
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

        $redis->tsinfo('non_existing_key');
    }

    public function argumentsProvider(): array
    {
        return [
            'with default arguments' => [
                ['key'],
                ['key'],
            ],
            'with RETENTION modifier' => [
                ['key', (new CreateArguments())->retentionMsecs(100)],
                ['key', 'RETENTION', 100],
            ],
            'with ENCODING modifier' => [
                ['key', (new CreateArguments())->encoding(CreateArguments::ENCODING_UNCOMPRESSED)],
                ['key', 'ENCODING', CreateArguments::ENCODING_UNCOMPRESSED],
            ],
            'with CHUNK_SIZE modifier' => [
                ['key', (new CreateArguments())->chunkSize(100)],
                ['key', 'CHUNK_SIZE', 100],
            ],
            'with DUPLICATE_POLICY modifier' => [
                ['key', (new CreateArguments())->duplicatePolicy(CommonArguments::POLICY_FIRST)],
                ['key', 'DUPLICATE_POLICY', CommonArguments::POLICY_FIRST],
            ],
            'with IGNORE modifier' => [
                ['key', (new CreateArguments())->ignore(10, 1.1)],
                ['key', 'IGNORE', 10, 1.1],
            ],
            'with all modifiers' => [
                ['key', (new CreateArguments())->retentionMsecs(100)->encoding(CreateArguments::ENCODING_UNCOMPRESSED)->chunkSize(100)->duplicatePolicy(CommonArguments::POLICY_FIRST)],
                ['key', 'RETENTION', 100, 'ENCODING', CreateArguments::ENCODING_UNCOMPRESSED, 'CHUNK_SIZE', 100, 'DUPLICATE_POLICY', CommonArguments::POLICY_FIRST],
            ],
        ];
    }
}
