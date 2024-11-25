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

namespace Predis\Command\Argument\Search\SchemaFields;

use PHPUnit\Framework\TestCase;

class NumericFieldTest extends TestCase
{
    /**
     * @dataProvider numericFieldsProvider
     * @param  array $arguments
     * @param  array $expectedSchema
     * @return void
     */
    public function testReturnsCorrectFieldArgumentsArray(
        array $arguments,
        array $expectedSchema
    ): void {
        $this->assertSame($expectedSchema, (new NumericField(...$arguments))->toArray());
    }

    public function numericFieldsProvider(): array
    {
        return [
            'with default arguments' => [
                ['field_name'],
                ['field_name', 'NUMERIC'],
            ],
            'with alias' => [
                ['field_name', 'fn'],
                ['field_name', 'AS', 'fn', 'NUMERIC'],
            ],
            'with sortable - no UNF' => [
                ['field_name', '', AbstractField::SORTABLE],
                ['field_name', 'NUMERIC', 'SORTABLE'],
            ],
            'with sortable - with UNF' => [
                ['field_name', '', AbstractField::SORTABLE_UNF],
                ['field_name', 'NUMERIC', 'SORTABLE', 'UNF'],
            ],
            'with NOINDEX modifier' => [
                ['field_name', '', AbstractField::NOT_SORTABLE, true],
                ['field_name', 'NUMERIC', 'NOINDEX'],
            ],
            'with INDEXMISSING modifier' => [
                ['field_name', '', AbstractField::NOT_SORTABLE, false, true],
                ['field_name', 'NUMERIC', 'INDEXMISSING'],
            ],
        ];
    }
}
