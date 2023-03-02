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

namespace Predis\Command\Argument\Search\SchemaFields;

use PHPUnit\Framework\TestCase;
use Predis\Command\Argument\Search\Schema;

class TagFieldTest extends TestCase
{
    /**
     * @dataProvider tagFieldsProvider
     * @param  array $arguments
     * @param  array $expectedSchema
     * @return void
     */
    public function testReturnsCorrectFieldArgumentsArray(
        array $arguments,
        array $expectedSchema
    ): void {
        $this->assertSame($expectedSchema, (new TagField(...$arguments))->toArray());
    }

    public function tagFieldsProvider(): array
    {
        return [
            'with default arguments' => [
                ['field_name'],
                ['field_name', 'TAG'],
            ],
            'with alias' => [
                ['field_name', 'fn'],
                ['field_name', 'AS', 'fn', 'TAG'],
            ],
            'with sortable - no UNF' => [
                ['field_name', '', Schema::SORTABLE],
                ['field_name', 'TAG', 'SORTABLE'],
            ],
            'with sortable - with UNF' => [
                ['field_name', '', Schema::SORTABLE_UNF],
                ['field_name', 'TAG', 'SORTABLE', 'UNF'],
            ],
            'with NOINDEX modifier' => [
                ['field_name', '', Schema::NOT_SORTABLE, true],
                ['field_name', 'TAG', 'NOINDEX'],
            ],
        ];
    }
}
