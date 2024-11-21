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

namespace Predis\Command\Argument\Search;

use PHPUnit\Framework\TestCase;

class AggregateArgumentsTest extends TestCase
{
    /**
     * @var AggregateArguments
     */
    private $arguments;

    protected function setUp(): void
    {
        $this->arguments = new AggregateArguments();
    }

    /**
     * @dataProvider loadProvider
     * @param  array $arguments
     * @param  array $expectedResponse
     * @return void
     */
    public function testCreatesArgumentsWithLoadModifier(array $arguments, array $expectedResponse): void
    {
        $this->arguments->load(...$arguments);

        $this->assertSame($expectedResponse, $this->arguments->toArray());
    }

    /**
     * @return void
     */
    public function testCreatesArgumentsWithGroupByModifier(): void
    {
        $this->arguments->groupBy('property1', 'property2');

        $this->assertSame(['GROUPBY', 2, 'property1', 'property2'], $this->arguments->toArray());
    }

    /**
     * @dataProvider reduceProvider
     * @param  array $arguments
     * @param  array $expectedResponse
     * @return void
     */
    public function testCreatesArgumentsWithReduceModifier(array $arguments, array $expectedResponse): void
    {
        $this->arguments->reduce(...$arguments);

        $this->assertSame($expectedResponse, $this->arguments->toArray());
    }

    /**
     * @dataProvider sortByProvider
     * @param  array $arguments
     * @param  array $expectedResponse
     * @return void
     */
    public function testCreatesArgumentsWithSortByModifier(array $arguments, array $expectedResponse): void
    {
        $this->arguments->sortBy(...$arguments);

        $this->assertSame($expectedResponse, $this->arguments->toArray());
    }

    /**
     * @dataProvider applyProvider
     * @param  array $arguments
     * @param  array $expectedResponse
     * @return void
     */
    public function testCreatesArgumentsWithApplyByModifier(array $arguments, array $expectedResponse): void
    {
        $this->arguments->apply(...$arguments);

        $this->assertSame($expectedResponse, $this->arguments->toArray());
    }

    /**
     * @dataProvider withCursorProvider
     * @param  array $arguments
     * @param  array $expectedResponse
     * @return void
     */
    public function testCreatesArgumentsWithWithCursorByModifier(array $arguments, array $expectedResponse): void
    {
        $this->arguments->withCursor(...$arguments);

        $this->assertSame($expectedResponse, $this->arguments->toArray());
    }

    public function loadProvider(): array
    {
        return [
            'with given fields' => [
                ['field1', 'field2'],
                ['LOAD', 2, 'field1', 'field2'],
            ],
            'with all fields' => [
                ['*'],
                ['LOAD', '*'],
            ],
        ];
    }

    public function reduceProvider(): array
    {
        return [
            'without aliases' => [
                ['function', 'arg1', 'arg2'],
                ['REDUCE', 'function', 2, 'arg1', 'arg2'],
            ],
            'with aliases' => [
                ['function', 'arg1', true, 'alias1', 'arg2', true, 'alias2'],
                ['REDUCE', 'function', 2, 'arg1', 'AS', 'alias1', 'arg2', 'AS', 'alias2'],
            ],
        ];
    }

    public function sortByProvider(): array
    {
        return [
            'without sorting direction and max value' => [
                [0, 'property1', 'property2'],
                ['SORTBY', 2, 'property1', 'property2'],
            ],
            'with sorting direction' => [
                [0, 'property1', 'ASC', 'property2', 'DESC'],
                ['SORTBY', 4, 'property1', 'ASC', 'property2', 'DESC'],
            ],
            'with max value' => [
                [2, 'property1', 'property2'],
                ['SORTBY', 2, 'property1', 'property2', 'MAX', 2],
            ],
            'with sorting direction and max value' => [
                [2, 'property1', 'ASC', 'property2', 'DESC'],
                ['SORTBY', 4, 'property1', 'ASC', 'property2', 'DESC', 'MAX', 2],
            ],
        ];
    }

    public function applyProvider(): array
    {
        return [
            'with default arguments' => [
                ['expression'],
                ['APPLY', 'expression'],
            ],
            'with alias' => [
                ['expression', 'name'],
                ['APPLY', 'expression', 'AS', 'name'],
            ],
        ];
    }

    public function withCursorProvider(): array
    {
        return [
            'with default arguments' => [
                [],
                ['WITHCURSOR'],
            ],
            'with readSize argument' => [
                [2],
                ['WITHCURSOR', 'COUNT', 2],
            ],
            'with maxIdle argument' => [
                [0, 2],
                ['WITHCURSOR', 'MAXIDLE', 2],
            ],
            'with readSize and maxIdle arguments' => [
                [3, 2],
                ['WITHCURSOR', 'COUNT', 3, 'MAXIDLE', 2],
            ],
        ];
    }
}
