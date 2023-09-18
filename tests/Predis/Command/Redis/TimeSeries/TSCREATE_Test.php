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
use Predis\Command\Redis\PredisCommandTestCase;
use Predis\Response\ServerException;

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
            'with all modifiers' => [
                ['key', (new CreateArguments())->retentionMsecs(100)->encoding(CreateArguments::ENCODING_UNCOMPRESSED)->chunkSize(100)->duplicatePolicy(CommonArguments::POLICY_FIRST)],
                ['key', 'RETENTION', 100, 'ENCODING', CreateArguments::ENCODING_UNCOMPRESSED, 'CHUNK_SIZE', 100, 'DUPLICATE_POLICY', CommonArguments::POLICY_FIRST],
            ],
        ];
    }
}
