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
use Predis\Command\Argument\TimeSeries\MGetArguments;
use Predis\Command\Redis\PredisCommandTestCase;

/**
 * @group commands
 * @group realm-stack
 */
class TSMGET_Test extends PredisCommandTestCase
{
    /**
     * {@inheritDoc}
     */
    protected function getExpectedCommand(): string
    {
        return TSMGET::class;
    }

    /**
     * {@inheritDoc}
     */
    protected function getExpectedId(): string
    {
        return 'TSMGET';
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
    public function testGetSampleFromMultipleTimeSeriesMatchingGivenPattern(): void
    {
        $redis = $this->getClient();
        $expectedResponse = [
            ['temperature:2:32', [['type', 'temp']], [123123123123, '27']],
            ['temperature:2:33', [['type', 'temp']], [123123123124, '27']],
        ];

        $createArguments = (new CreateArguments())
            ->retentionMsecs(60000)
            ->duplicatePolicy(CommonArguments::POLICY_MAX)
            ->labels('type', 'temp', 'sensor_id', 2, 'area_id', 32);

        $this->assertEquals(
            'OK',
            $redis->tscreate('temperature:2:32', $createArguments)
        );

        $this->assertEquals(
            'OK',
            $redis->tscreate('temperature:2:33', $createArguments)
        );

        $redis->tsadd('temperature:2:32', 123123123123, 27);
        $redis->tsadd('temperature:2:33', 123123123124, 27);

        $this->assertEquals(
            $expectedResponse,
            $redis->tsmget((new MGetArguments())->selectedLabels('type'), 'type=temp')
        );
    }

    /**
     * @group connected
     * @return void
     * @requiresRedisTimeSeriesVersion >= 1.10.0
     */
    public function testGetSampleFromMultipleTimeSeriesMatchingGivenPatternResp3(): void
    {
        $redis = $this->getResp3Client();
        $expectedResponse = [
            'temperature:2:32' => [['type' => 'temp'], [123123123123, 27]],
            'temperature:2:33' => [['type' => 'temp'], [123123123124, 27]],
        ];

        $createArguments = (new CreateArguments())
            ->retentionMsecs(60000)
            ->duplicatePolicy(CommonArguments::POLICY_MAX)
            ->labels('type', 'temp', 'sensor_id', 2, 'area_id', 32);

        $this->assertEquals(
            'OK',
            $redis->tscreate('temperature:2:32', $createArguments)
        );

        $this->assertEquals(
            'OK',
            $redis->tscreate('temperature:2:33', $createArguments)
        );

        $redis->tsadd('temperature:2:32', 123123123123, 27);
        $redis->tsadd('temperature:2:33', 123123123124, 27);

        $this->assertEquals(
            $expectedResponse,
            $redis->tsmget((new MGetArguments())->selectedLabels('type'), 'type=temp')
        );
    }

    public function argumentsProvider(): array
    {
        return [
            'with default arguments' => [
                [(new MGetArguments())->withLabels(), 'filterExpression1', 'filterExpression2'],
                ['WITHLABELS', 'FILTER', 'filterExpression1', 'filterExpression2'],
            ],
            'with LATEST modifier' => [
                [(new MGetArguments())->latest(), 'filterExpression1', 'filterExpression2'],
                ['LATEST', 'FILTER', 'filterExpression1', 'filterExpression2'],
            ],
            'with WITHLABELS modifier' => [
                [(new MGetArguments())->withLabels(), 'filterExpression1', 'filterExpression2'],
                ['WITHLABELS', 'FILTER', 'filterExpression1', 'filterExpression2'],
            ],
            'with SELECTED_LABELS modifier' => [
                [(new MGetArguments())->selectedLabels('label1', 'label2'), 'filterExpression1', 'filterExpression2'],
                ['SELECTED_LABELS', 'label1', 'label2', 'FILTER', 'filterExpression1', 'filterExpression2'],
            ],
            'with all modifiers' => [
                [(new MGetArguments())->latest()->selectedLabels('label1', 'label2'), 'filterExpression1', 'filterExpression2'],
                ['LATEST', 'SELECTED_LABELS', 'label1', 'label2', 'FILTER', 'filterExpression1', 'filterExpression2'],
            ],
        ];
    }
}
