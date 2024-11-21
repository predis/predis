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

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class SearchArgumentsTest extends TestCase
{
    /**
     * @var SearchArguments
     */
    private $arguments;

    protected function setUp(): void
    {
        $this->arguments = new SearchArguments();
    }

    /**
     * @return void
     */
    public function testCreatesArgumentsWithNoContentModifier(): void
    {
        $this->arguments->noContent();

        $this->assertSame(['NOCONTENT'], $this->arguments->toArray());
    }

    /**
     * @return void
     */
    public function testCreatesArgumentsWithWithSortKeysModifier(): void
    {
        $this->arguments->withSortKeys();

        $this->assertSame(['WITHSORTKEYS'], $this->arguments->toArray());
    }

    /**
     * @return void
     */
    public function testCreatesArgumentsWithSearchFilterModifier(): void
    {
        $this->arguments->searchFilter(['numeric_field', 1, 10], ['numeric_field1', 2, 5]);

        $this->assertSame(
            ['FILTER', 'numeric_field', 1, 10, 'FILTER', 'numeric_field1', 2, 5],
            $this->arguments->toArray()
        );
    }

    /**
     * @return void
     */
    public function testCreatesArgumentsWithGeoFilterModifier(): void
    {
        $this->arguments->geoFilter(
            ['geo_field', 13.2321, 14.2321, 300, 'km'],
            ['geo_field1', 15.231, 16.234, 210, 'km']
        );

        $this->assertSame(
            ['GEOFILTER', 'geo_field', 13.2321, 14.2321, 300, 'km', 'GEOFILTER', 'geo_field1', 15.231, 16.234, 210, 'km'],
            $this->arguments->toArray()
        );
    }

    /**
     * @return void
     */
    public function testCreatesArgumentsWithInKeysModifier(): void
    {
        $this->arguments->inKeys(['key1', 'key2']);

        $this->assertSame(['INKEYS', 2, 'key1', 'key2'], $this->arguments->toArray());
    }

    /**
     * @return void
     */
    public function testCreatesArgumentsWithInFieldsModifier(): void
    {
        $this->arguments->inFields(['field1', 'field2']);

        $this->assertSame(['INFIELDS', 2, 'field1', 'field2'], $this->arguments->toArray());
    }

    /**
     * @return void
     */
    public function testCreatesArgumentsWithReturnModifier(): void
    {
        $this->arguments->addReturn(2, 'identifier', true, 'property', 'identifier2', 'identifier3');

        $this->assertSame(
            ['RETURN', 2, 'identifier', 'AS', 'property', 'identifier2', 'identifier3'],
            $this->arguments->toArray()
        );
    }

    /**
     * @dataProvider summarizeProvider
     * @param  array $arguments
     * @param  array $expectedResponse
     * @return void
     */
    public function testCreatesArgumentsWithSummarizeModifier(array $arguments, array $expectedResponse): void
    {
        $this->arguments->summarize(...$arguments);

        $this->assertSame($expectedResponse, $this->arguments->toArray());
    }

    /**
     * @dataProvider highlightProvider
     * @param  array $arguments
     * @param  array $expectedResponse
     * @return void
     */
    public function testCreatesArgumentsWithHighlightModifier(array $arguments, array $expectedResponse): void
    {
        $this->arguments->highlight(...$arguments);

        $this->assertSame($expectedResponse, $this->arguments->toArray());
    }

    /**
     * @return void
     */
    public function testCreatesArgumentsWithSlopModifier(): void
    {
        $this->arguments->slop(2);

        $this->assertSame(['SLOP', 2], $this->arguments->toArray());
    }

    /**
     * @return void
     */
    public function testCreatesArgumentsWithInOrderModifier(): void
    {
        $this->arguments->inOrder();

        $this->assertSame(['INORDER'], $this->arguments->toArray());
    }

    /**
     * @return void
     */
    public function testCreatesArgumentsWithExpanderModifier(): void
    {
        $this->arguments->expander('expander');

        $this->assertSame(['EXPANDER', 'expander'], $this->arguments->toArray());
    }

    /**
     * @return void
     */
    public function testCreatesArgumentsWithScorerModifier(): void
    {
        $this->arguments->scorer('scorer');

        $this->assertSame(['SCORER', 'scorer'], $this->arguments->toArray());
    }

    /**
     * @return void
     */
    public function testCreatesArgumentsExplainScoreModifier(): void
    {
        $this->arguments->explainScore();

        $this->assertSame(['EXPLAINSCORE'], $this->arguments->toArray());
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
     * @return void
     */
    public function testThrowsExceptionOnSortByWrongOrderByModifier(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Wrong order direction value given. Currently supports: ASC, DESC');

        $this->arguments->sortBy('sort_attribute', 'wrong');
    }

    /**
     * @return void
     */
    public function testCreatesCorrectFTSearchArgumentsSetOnMethodsChainCall(): void
    {
        $this->arguments->withScores();
        $this->arguments->withPayloads();
        $this->arguments->searchFilter(['numeric_field', 1, 10]);
        $this->arguments->addReturn(2, 'identifier', true, 'property');

        $this->assertSame(
            ['WITHSCORES', 'WITHPAYLOADS', 'FILTER', 'numeric_field', 1, 10, 'RETURN', 2, 'identifier', 'AS', 'property'],
            $this->arguments->toArray()
        );
    }

    public function sortByProvider(): array
    {
        return [
            'with default arguments' => [
                ['sort_attribute'],
                ['SORTBY', 'sort_attribute', 'ASC'],
            ],
            'with DESC modifier' => [
                ['sort_attribute', 'desc'],
                ['SORTBY', 'sort_attribute', 'DESC'],
            ],
        ];
    }

    public function summarizeProvider(): array
    {
        return [
            'with no arguments' => [
                [],
                ['SUMMARIZE'],
            ],
            'with fields only' => [
                [['field1', 'field2']],
                ['SUMMARIZE', 'FIELDS', 2, 'field1', 'field2'],
            ],
            'with non-default FRAGS' => [
                [['field1', 'field2'], 2],
                ['SUMMARIZE', 'FIELDS', 2, 'field1', 'field2', 'FRAGS', 2],
            ],
            'with non-default LEN' => [
                [['field1', 'field2'], 0, 2],
                ['SUMMARIZE', 'FIELDS', 2, 'field1', 'field2', 'LEN', 2],
            ],
            'with non-default SEPARATOR' => [
                [['field1', 'field2'], 0, 0, ','],
                ['SUMMARIZE', 'FIELDS', 2, 'field1', 'field2', 'SEPARATOR', ','],
            ],
            'with all arguments' => [
                [['field1', 'field2'], 2, 2, ','],
                ['SUMMARIZE', 'FIELDS', 2, 'field1', 'field2', 'FRAGS', 2, 'LEN', 2, 'SEPARATOR', ','],
            ],
        ];
    }

    public function highlightProvider(): array
    {
        return [
            'with no arguments' => [
                [],
                ['HIGHLIGHT'],
            ],
            'with fields only' => [
                [['field1', 'field2']],
                ['HIGHLIGHT', 'FIELDS', 2, 'field1', 'field2'],
            ],
            'with open tag only' => [
                [['field1', 'field2'], 'openTag'],
                ['HIGHLIGHT', 'FIELDS', 2, 'field1', 'field2'],
            ],
            'with close tag only' => [
                [['field1', 'field2'], '', 'closeTag'],
                ['HIGHLIGHT', 'FIELDS', 2, 'field1', 'field2'],
            ],
            'with both tags' => [
                [['field1', 'field2'], 'openTag', 'closeTag'],
                ['HIGHLIGHT', 'FIELDS', 2, 'field1', 'field2', 'TAGS', 'openTag', 'closeTag'],
            ],
        ];
    }
}
