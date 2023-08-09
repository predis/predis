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

namespace Predis\Command\Argument;

use PredisTestCase;

class CommandListFilterTest extends PredisTestCase
{
    /**
     * @var CommandListFilter
     */
    private $filter;

    protected function setUp(): void
    {
        parent::setUp();

        $this->filter = new CommandListFilter();
    }

    /**
     * @group disconnected
     * @return void
     */
    public function testFilterByModule(): void
    {
        $this->assertEquals(
            ['FILTERBY', 'MODULE', 'module'],
            $this->filter->filterByModule('module')->toArray()
        );
    }

    /**
     * @group disconnected
     * @return void
     */
    public function testFilterByACLCat(): void
    {
        $this->assertEquals(
            ['FILTERBY', 'ACLCAT', 'category'],
            $this->filter->filterByACLCategory('category')->toArray()
        );
    }

    /**
     * @group disconnected
     * @return void
     */
    public function testFilterByPattern(): void
    {
        $this->assertEquals(
            ['FILTERBY', 'PATTERN', 'pattern'],
            $this->filter->filterByPattern('pattern')->toArray()
        );
    }

    /**
     * @group disconnected
     * @return void
     */
    public function testToArray(): void
    {
        $this->assertEquals(
            ['FILTERBY'],
            $this->filter->toArray()
        );
    }
}
