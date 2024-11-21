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

namespace Predis\Command\Argument\TimeSeries;

use PHPUnit\Framework\TestCase;

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
}
