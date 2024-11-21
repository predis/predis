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

use Predis\Command\Argument\TimeSeries\CreateArguments;
use Predis\Command\Argument\TimeSeries\MRangeArguments;
use Predis\Command\Redis\PredisCommandTestCase;

/**
 * @group commands
 * @group realm-stack
 */
class TSMREVRANGE_Test extends PredisCommandTestCase
{
    /**
     * {@inheritDoc}
     */
    protected function getExpectedCommand(): string
    {
        return TSMREVRANGE::class;
    }

    /**
     * {@inheritDoc}
     */
    protected function getExpectedId(): string
    {
        return 'TSMREVRANGE';
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
     * @requiresRedisTimeSeriesVersion >= 1.4.0
     */
    public function testQueryRangeAcrossMultipleTimeSeriesInReverseDirection(): void
    {
        $redis = $this->getClient();
        $expectedResponse = [
            [
                'type=stock',
                [
                    ['type', 'stock'],
                    ['__reducer__', 'max'],
                    ['__source__', 'stock:A,stock:B'],
                ],
                [
                    [1020, '120'],
                    [1010, '110'],
                    [1000, '120'],
                ],
            ],
        ];

        $this->assertEquals(
            'OK',
            $redis->tscreate('stock:A', (new CreateArguments())->labels('type', 'stock', 'name', 'A'))
        );
        $this->assertEquals(
            'OK',
            $redis->tscreate('stock:B', (new CreateArguments())->labels('type', 'stock', 'name', 'B'))
        );
        $this->assertSame(
            [1000, 1010, 1020],
            $redis->tsmadd('stock:A', 1000, 100, 'stock:A', 1010, 110, 'stock:A', 1020, 120)
        );
        $this->assertSame(
            [1000, 1010, 1020],
            $redis->tsmadd('stock:B', 1000, 120, 'stock:B', 1010, 110, 'stock:B', 1020, 100)
        );

        $mrangeArguments = (new MRangeArguments())
            ->withLabels()
            ->filter('type=stock')
            ->groupBy('type', 'max');

        $this->assertEquals($expectedResponse, $redis->tsmrevrange('-', '+', $mrangeArguments));
    }

    public function argumentsProvider(): array
    {
        return [
            'with default arguments' => [
                [1000, 1001, (new MRangeArguments())->filter('filterExpression1', 'filterExpression2')],
                [1000, 1001, 'FILTER', 'filterExpression1', 'filterExpression2'],
            ],
            'with LATEST modifier' => [
                [1000, 1001, (new MRangeArguments())->latest()->filter('filterExpression1', 'filterExpression2')],
                [1000, 1001, 'LATEST', 'FILTER', 'filterExpression1', 'filterExpression2'],
            ],
            'with FILTER_BY_TS modifier' => [
                [1000, 1001, (new MRangeArguments())->filterByTs(1000, 1001)->filter('filterExpression1', 'filterExpression2')],
                [1000, 1001, 'FILTER_BY_TS', 1000, 1001, 'FILTER', 'filterExpression1', 'filterExpression2'],
            ],
            'with FILTER_BY_VALUE modifier' => [
                [1000, 1001, (new MRangeArguments())->filterByValue(1000, 1001)->filter('filterExpression1', 'filterExpression2')],
                [1000, 1001, 'FILTER_BY_VALUE', 1000, 1001, 'FILTER', 'filterExpression1', 'filterExpression2'],
            ],
            'with WITHLABELS modifier' => [
                [1000, 1001, (new MRangeArguments())->withLabels()->filter('filterExpression1', 'filterExpression2')],
                [1000, 1001, 'WITHLABELS', 'FILTER', 'filterExpression1', 'filterExpression2'],
            ],
            'with SELECTED_LABELS modifier' => [
                [1000, 1001, (new MRangeArguments())->selectedLabels('label1', 'label2')->filter('filterExpression1', 'filterExpression2')],
                [1000, 1001, 'SELECTED_LABELS', 'label1', 'label2', 'FILTER', 'filterExpression1', 'filterExpression2'],
            ],
            'with COUNT modifier' => [
                [1000, 1001, (new MRangeArguments())->count(2)->filter('filterExpression1', 'filterExpression2')],
                [1000, 1001, 'COUNT', 2, 'FILTER', 'filterExpression1', 'filterExpression2'],
            ],
            'with AGGREGATION modifier - default arguments' => [
                [1000, 1001, (new MRangeArguments())->aggregation('sum', 2)->filter('filterExpression1', 'filterExpression2')],
                [1000, 1001, 'AGGREGATION', 'sum', 2, 'FILTER', 'filterExpression1', 'filterExpression2'],
            ],
            'with AGGREGATION modifier - with ALIGN' => [
                [1000, 1001, (new MRangeArguments())->aggregation('sum', 2, 2)->filter('filterExpression1', 'filterExpression2')],
                [1000, 1001, 'ALIGN', 2, 'AGGREGATION', 'sum', 2, 'FILTER', 'filterExpression1', 'filterExpression2'],
            ],
            'with AGGREGATION modifier - with BUCKETTIMESTAMP' => [
                [1000, 1001, (new MRangeArguments())->aggregation('sum', 2, 0, 10000)->filter('filterExpression1', 'filterExpression2')],
                [1000, 1001, 'AGGREGATION', 'sum', 2, 'BUCKETTIMESTAMP', 10000, 'FILTER', 'filterExpression1', 'filterExpression2'],
            ],
            'with AGGREGATION modifier - with EMPTY' => [
                [1000, 1001, (new MRangeArguments())->aggregation('sum', 2, 0, 0, true)->filter('filterExpression1', 'filterExpression2')],
                [1000, 1001, 'AGGREGATION', 'sum', 2, 'EMPTY', 'FILTER', 'filterExpression1', 'filterExpression2'],
            ],
            'with GROUPBY modifier' => [
                [1000, 1001, (new MRangeArguments())->filter('filterExpression1', 'filterExpression2')->groupBy('label', 'reducer')],
                [1000, 1001, 'FILTER', 'filterExpression1', 'filterExpression2', 'GROUPBY', 'label', 'REDUCE', 'reducer'],
            ],
            'with all modifiers' => [
                [1000, 1001, (new MRangeArguments())->latest()->filterByTs(1000, 1001)->filterByValue(1000, 1001)->withLabels()->count(2)->aggregation('sum', 2)->filter('filterExpression1', 'filterExpression2')->groupBy('label', 'reducer'), 'filterExpression1', 'filterExpression2'],
                [1000, 1001, 'LATEST', 'FILTER_BY_TS', 1000, 1001, 'FILTER_BY_VALUE', 1000, 1001, 'WITHLABELS', 'COUNT', 2, 'AGGREGATION', 'sum', 2, 'FILTER', 'filterExpression1', 'filterExpression2', 'GROUPBY', 'label', 'REDUCE', 'reducer'],
            ],
        ];
    }
}
