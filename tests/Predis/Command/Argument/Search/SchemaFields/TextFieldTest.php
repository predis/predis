<?php

/*
 * This file is part of the Predis package.
 *
 * (c) 2009-2020 Daniele Alessandri
 * (c) 2021-2025 Till Krüss
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Command\Argument\Search\SchemaFields;

use PHPUnit\Framework\TestCase;

class TextFieldTest extends TestCase
{
    /**
     * @dataProvider textFieldsProvider
     * @param  array $arguments
     * @param  array $expectedSchema
     * @return void
     */
    public function testReturnsCorrectFieldArgumentsArray(
        array $arguments,
        array $expectedSchema
    ): void {
        $this->assertSame($expectedSchema, (new TextField(...$arguments))->toArray());
    }

    public function textFieldsProvider(): array
    {
        return [
            'with default arguments' => [
                ['field_name'],
                ['field_name', 'TEXT'],
            ],
            'with alias' => [
                ['field_name', 'fn'],
                ['field_name', 'AS', 'fn', 'TEXT'],
            ],
            'with sortable - no UNF' => [
                ['field_name', '', AbstractField::SORTABLE],
                ['field_name', 'TEXT', 'SORTABLE'],
            ],
            'with sortable - with UNF' => [
                ['field_name', '', AbstractField::SORTABLE_UNF],
                ['field_name', 'TEXT', 'SORTABLE', 'UNF'],
            ],
            'with NOINDEX modifier' => [
                ['field_name', '', AbstractField::NOT_SORTABLE, true],
                ['field_name', 'TEXT', 'NOINDEX'],
            ],
            'with INDEXEMPTY modifier' => [
                ['field_name', '', AbstractField::NOT_SORTABLE, false, false, '', 1, false, true],
                ['field_name', 'TEXT', 'INDEXEMPTY'],
            ],
            'with INDEXMISSING modifier' => [
                ['field_name', '', AbstractField::NOT_SORTABLE, false, false, '', 1, false, false, true],
                ['field_name', 'TEXT', 'INDEXMISSING'],
            ],
        ];
    }
}
