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

class NRangeArgumentsTest extends TestCase
{
    /**
     * @var NRangeArguments
     */
    private $arguments;

    protected function setUp(): void
    {
        $this->arguments = new NRangeArguments();
    }

    /**
     * @return void
     */
    public function testInheritsCommonRangeModifiers(): void
    {
        $this->arguments
            ->latest()
            ->filterByTs(1000, 1001)
            ->filterByValue(1000, 1001)
            ->count(100);

        $this->assertSame(
            ['LATEST', 'FILTER_BY_TS', 1000, 1001, 'FILTER_BY_VALUE', 1000, 1001, 'COUNT', 100],
            $this->arguments->toArray()
        );
    }

    /**
     * @dataProvider aggregatorProvider
     * @param  array $arguments
     * @param  array $expectedResponse
     * @return void
     */
    public function testCreatesArgumentsWithAggregatorModifierAsSeparateTokens(array $arguments, array $expectedResponse): void
    {
        $this->arguments->aggregation(...$arguments);

        $this->assertSame($expectedResponse, $this->arguments->toArray());
    }

    public function aggregatorProvider(): array
    {
        return [
            'with single aggregator' => [
                [NRangeArguments::AGG_SUM, 1000],
                ['AGGREGATION', NRangeArguments::AGG_SUM, 1000],
            ],
            'with multiple aggregators as array' => [
                [[NRangeArguments::AGG_MIN, NRangeArguments::AGG_MAX], 1000],
                ['AGGREGATION', 'min', 'max', 1000],
            ],
            'with multiple aggregators as comma-separated string' => [
                ['min,max', 1000],
                ['AGGREGATION', 'min', 'max', 1000],
            ],
            'with ALIGN modifier' => [
                [[NRangeArguments::AGG_MIN, NRangeArguments::AGG_MAX], 1000, 10],
                ['ALIGN', 10, 'AGGREGATION', 'min', 'max', 1000],
            ],
            'with multiple aggregators and all arguments' => [
                [[NRangeArguments::AGG_MIN, NRangeArguments::AGG_MAX], 1000, 10, 10000, true],
                ['ALIGN', 10, 'AGGREGATION', 'min', 'max', 1000, 'BUCKETTIMESTAMP', 10000, 'EMPTY'],
            ],
        ];
    }
}
