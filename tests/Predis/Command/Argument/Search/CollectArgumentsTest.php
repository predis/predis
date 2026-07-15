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

namespace Predis\Command\Argument\Search;

use PHPUnit\Framework\TestCase;

class CollectArgumentsTest extends TestCase
{
    /**
     * @var CollectArguments
     */
    private $arguments;

    protected function setUp(): void
    {
        $this->arguments = new CollectArguments();
    }

    /**
     * @return void
     */
    public function testAllFields(): void
    {
        $this->arguments->allFields();

        $this->assertSame(['FIELDS', '*'], $this->arguments->toArray());
    }

    /**
     * @return void
     */
    public function testFieldsWithExplicitNames(): void
    {
        $this->arguments->fields('@name', '@sweetness');

        $this->assertSame(['FIELDS', 2, '@name', '@sweetness'], $this->arguments->toArray());
    }

    /**
     * @return void
     */
    public function testFieldsNormalizesMissingAtPrefix(): void
    {
        $this->arguments->fields('name', '@sweetness', '__key');

        $this->assertSame(['FIELDS', 3, '@name', '@sweetness', '@__key'], $this->arguments->toArray());
    }

    /**
     * @return void
     */
    public function testDistinct(): void
    {
        $this->arguments->allFields()->distinct();

        $this->assertSame(['FIELDS', '*', 'DISTINCT'], $this->arguments->toArray());
    }

    /**
     * @dataProvider sortByProvider
     * @param  array $sortByFields
     * @param  array $expectedResponse
     * @return void
     */
    public function testSortBy(array $sortByFields, array $expectedResponse): void
    {
        $this->arguments->sortBy($sortByFields);

        $this->assertSame($expectedResponse, $this->arguments->toArray());
    }

    /**
     * @return void
     */
    public function testLimit(): void
    {
        $this->arguments->limit(0, 5);

        $this->assertSame(['LIMIT', 0, 5], $this->arguments->toArray());
    }

    /**
     * @return void
     */
    public function testCombinesAllClausesInCanonicalOrder(): void
    {
        $this->arguments
            ->fields('@name', '@sweetness')
            ->sortBy(['@sweetness' => CollectArguments::SORT_DESC])
            ->limit(0, 2);

        $this->assertSame(
            ['FIELDS', 2, '@name', '@sweetness', 'SORTBY', 2, '@sweetness', 'DESC', 'LIMIT', 0, 2],
            $this->arguments->toArray()
        );
    }

    public function sortByProvider(): array
    {
        return [
            'single key descending' => [
                ['@sweetness' => CollectArguments::SORT_DESC],
                ['SORTBY', 2, '@sweetness', 'DESC'],
            ],
            'single key ascending' => [
                ['@name' => CollectArguments::SORT_ASC],
                ['SORTBY', 2, '@name', 'ASC'],
            ],
            'multiple keys' => [
                ['@sweetness' => CollectArguments::SORT_DESC, '@name' => CollectArguments::SORT_ASC],
                ['SORTBY', 4, '@sweetness', 'DESC', '@name', 'ASC'],
            ],
            'normalizes missing @ prefix' => [
                ['sweetness' => CollectArguments::SORT_DESC],
                ['SORTBY', 2, '@sweetness', 'DESC'],
            ],
        ];
    }
}
