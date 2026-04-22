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

namespace Predis\Command\Argument\TimeSeries;

use PHPUnit\Framework\TestCase;

class RangeArgumentsTest extends TestCase
{
    /**
     * @var RangeArguments
     */
    private $arguments;

    protected function setUp(): void
    {
        $this->arguments = new RangeArguments();
    }

    /**
     * @return void
     */
    public function testCreatesArgumentsWithFilterByTsModifier(): void
    {
        $this->arguments->filterByTs(1000, 1001);

        $this->assertSame(['FILTER_BY_TS', 1000, 1001], $this->arguments->toArray());
    }

    /**
     * @return void
     */
    public function testCreatesArgumentsWithFilterByValueModifier(): void
    {
        $this->arguments->filterByValue(1000, 1001);

        $this->assertSame(['FILTER_BY_VALUE', 1000, 1001], $this->arguments->toArray());
    }

    /**
     * @return void
     */
    public function testCreatesArgumentsWithCountModifier(): void
    {
        $this->arguments->count(1000);

        $this->assertSame(['COUNT', 1000], $this->arguments->toArray());
    }

    /**
     * @dataProvider aggregatorProvider
     * @param  array $arguments
     * @param  array $expectedResponse
     * @return void
     */
    public function testCreatesArgumentsWithAggregatorModifier(array $arguments, array $expectedResponse): void
    {
        $this->arguments->aggregation(...$arguments);

        $this->assertSame($expectedResponse, $this->arguments->toArray());
    }

    public function aggregatorProvider(): array
    {
        return [
            'with default arguments' => [
                [RangeArguments::AGG_SUM, 1000],
                ['AGGREGATION', RangeArguments::AGG_SUM, 1000],
            ],
            'with ALIGN modifier' => [
                [RangeArguments::AGG_COUNT, 1000, 10],
                ['ALIGN', 10, 'AGGREGATION', RangeArguments::AGG_COUNT, 1000],
            ],
            'with BUCKETTIMESTAMP modifier' => [
                [RangeArguments::AGG_MAX, 1000, 0, 10000],
                ['AGGREGATION', RangeArguments::AGG_MAX, 1000, 'BUCKETTIMESTAMP', 10000],
            ],
            'with EMPTY modifier' => [
                [RangeArguments::AGG_MIN, 1000, 0, 0, true],
                ['AGGREGATION', RangeArguments::AGG_MIN, 1000, 'EMPTY'],
            ],
            'with all arguments' => [
                [RangeArguments::AGG_COUNT_NAN, 1000, 10, 10000, true],
                ['ALIGN', 10, 'AGGREGATION', RangeArguments::AGG_COUNT_NAN, 1000, 'BUCKETTIMESTAMP', 10000, 'EMPTY'],
            ],
            'with multiple aggregators as array' => [
                [[RangeArguments::AGG_MIN, RangeArguments::AGG_MAX], 1000],
                ['AGGREGATION', 'min,max', 1000],
            ],
            'with multiple aggregators as comma-separated string' => [
                ['min,max', 1000],
                ['AGGREGATION', 'min,max', 1000],
            ],
            'with multiple aggregators and all arguments' => [
                [[RangeArguments::AGG_MIN, RangeArguments::AGG_MAX, RangeArguments::AGG_SUM], 1000, 10, 10000, true],
                ['ALIGN', 10, 'AGGREGATION', 'min,max,sum', 1000, 'BUCKETTIMESTAMP', 10000, 'EMPTY'],
            ],
            'with single-element aggregator array' => [
                [[RangeArguments::AGG_SUM], 1000],
                ['AGGREGATION', RangeArguments::AGG_SUM, 1000],
            ],
        ];
    }
}
