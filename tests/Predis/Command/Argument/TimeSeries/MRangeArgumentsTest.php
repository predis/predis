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
use UnexpectedValueException;

class MRangeArgumentsTest extends TestCase
{
    /**
     * @var MRangeArguments
     */
    private $arguments;

    protected function setUp(): void
    {
        $this->arguments = new MRangeArguments();
    }

    /**
     * @return void
     */
    public function testCreatesArgumentsWithFilterModifier(): void
    {
        $this->arguments->filter('exp1', 'exp2');

        $this->assertSame(['FILTER', 'exp1', 'exp2'], $this->arguments->toArray());
    }

    /**
     * @return void
     */
    public function testCreatesArgumentsWithGroupByModifier(): void
    {
        $this->arguments->groupBy('label', 'reducer');

        $this->assertSame(['GROUPBY', 'label', 'REDUCE', 'reducer'], $this->arguments->toArray());
    }

    /**
     * @return void
     */
    public function testThrowsOnGroupByWhenMultipleAggregatorsAlreadySet(): void
    {
        $this->arguments->aggregation([RangeArguments::AGG_MIN, RangeArguments::AGG_MAX], 1000);

        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('GROUPBY cannot be combined with multiple aggregators.');

        $this->arguments->groupBy('label', 'reducer');
    }

    /**
     * @return void
     */
    public function testThrowsOnMultipleAggregatorsWhenGroupByAlreadySet(): void
    {
        $this->arguments->groupBy('label', 'reducer');

        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('Multiple aggregators cannot be combined with GROUPBY.');

        $this->arguments->aggregation([RangeArguments::AGG_MIN, RangeArguments::AGG_MAX], 1000);
    }

    /**
     * @return void
     */
    public function testAllowsGroupByAfterSingleAggregator(): void
    {
        $this->arguments->aggregation(RangeArguments::AGG_SUM, 1000);
        $this->arguments->groupBy('type', 'max');

        $this->assertSame(
            ['AGGREGATION', RangeArguments::AGG_SUM, 1000, 'GROUPBY', 'type', 'REDUCE', 'max'],
            $this->arguments->toArray()
        );
    }

    /**
     * @return void
     */
    public function testAllowsSingleAggregatorAfterGroupBy(): void
    {
        $this->arguments->groupBy('type', 'max');
        $this->arguments->aggregation(RangeArguments::AGG_SUM, 1000);

        $this->assertSame(
            ['GROUPBY', 'type', 'REDUCE', 'max', 'AGGREGATION', RangeArguments::AGG_SUM, 1000],
            $this->arguments->toArray()
        );
    }
}
