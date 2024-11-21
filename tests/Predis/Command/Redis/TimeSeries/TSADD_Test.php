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

use Predis\Command\Argument\TimeSeries\AddArguments;
use Predis\Command\Argument\TimeSeries\CommonArguments;
use Predis\Command\Argument\TimeSeries\CreateArguments;
use Predis\Command\Redis\PredisCommandTestCase;
use UnexpectedValueException;

/**
 * @group commands
 * @group realm-stack
 */
class TSADD_Test extends PredisCommandTestCase
{
    /**
     * {@inheritDoc}
     */
    protected function getExpectedCommand(): string
    {
        return TSADD::class;
    }

    /**
     * {@inheritDoc}
     */
    protected function getExpectedId(): string
    {
        return 'TSADD';
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

        $command->setArguments(['key', 123123121321, 1.0, (new AddArguments())->ignore(-2, -1)]);
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
    public function testAddSampleIntoTimeSeriesWithGivenConfiguration(): void
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

        $addArguments = (new AddArguments())
            ->retentionMsecs(31536000000);

        $this->assertEquals(
            123123123123,
            $redis->tsadd('temperature:2:32', 123123123123, 27, $addArguments)
        );
    }

    /**
     * @group connected
     * @group relay-resp3
     * @return void
     * @requiresRedisTimeSeriesVersion >= 1.12.01
     */
    public function testAddSampleIntoTimeSeriesWithIgnoreArgument(): void
    {
        $redis = $this->getClient();

        $createArguments = (new CreateArguments())
            ->retentionMsecs(31536000000)
            ->duplicatePolicy(CommonArguments::POLICY_LAST)
            ->ignore(10, 10);

        $this->assertEquals('OK', $redis->tscreate('temperature:2:32', $createArguments));

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

    public function argumentsProvider(): array
    {
        return [
            'with default arguments' => [
                ['key', 123123121321, 1.0],
                ['key', 123123121321, 1.0],
            ],
            'with RETENTION modifier' => [
                ['key', 123123121321, 1.0, (new AddArguments())->retentionMsecs(100)],
                ['key', 123123121321, 1.0, 'RETENTION', 100],
            ],
            'with ENCODING modifier' => [
                ['key', 123123121321, 1.0, (new AddArguments())->encoding(CommonArguments::ENCODING_UNCOMPRESSED)],
                ['key', 123123121321, 1.0, 'ENCODING', CommonArguments::ENCODING_UNCOMPRESSED],
            ],
            'with CHUNK_SIZE modifier' => [
                ['key', 123123121321, 1.0, (new AddArguments())->chunkSize(100)],
                ['key', 123123121321, 1.0, 'CHUNK_SIZE', 100],
            ],
            'with ON_DUPLICATE modifier' => [
                ['key', 123123121321, 1.0, (new AddArguments())->onDuplicate(CommonArguments::POLICY_FIRST)],
                ['key', 123123121321, 1.0, 'ON_DUPLICATE', CommonArguments::POLICY_FIRST],
            ],
            'with IGNORE modifier' => [
                ['key', 123123121321, 1.0, (new AddArguments())->ignore(10, 1.1)],
                ['key', 123123121321, 1.0, 'IGNORE', 10, 1.1],
            ],
            'with all modifiers' => [
                ['key', 123123121321, 1.0, (new AddArguments())->retentionMsecs(100)->encoding(CommonArguments::ENCODING_UNCOMPRESSED)->chunkSize(100)->onDuplicate(CommonArguments::POLICY_FIRST)],
                ['key', 123123121321, 1.0, 'RETENTION', 100, 'ENCODING', CommonArguments::ENCODING_UNCOMPRESSED, 'CHUNK_SIZE', 100, 'ON_DUPLICATE', CommonArguments::POLICY_FIRST],
            ],
        ];
    }
}
