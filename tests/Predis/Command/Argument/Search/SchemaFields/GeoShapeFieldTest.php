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

class GeoShapeFieldTest extends TestCase
{
    /**
     * @dataProvider geoFieldsProvider
     * @param  array $arguments
     * @param  array $expectedSchema
     * @return void
     */
    public function testReturnsCorrectFieldArgumentsArray(
        array $arguments,
        array $expectedSchema
    ): void {
        $this->assertSame($expectedSchema, (new GeoShapeField(...$arguments))->toArray());
    }

    public function geoFieldsProvider(): array
    {
        return [
            'with default arguments' => [
                ['field_name'],
                ['field_name', 'GEOSHAPE'],
            ],
            'with alias' => [
                ['field_name', 'fn'],
                ['field_name', 'AS', 'fn', 'GEOSHAPE'],
            ],
            'with sortable - no UNF' => [
                ['field_name', '', AbstractField::SORTABLE],
                ['field_name', 'GEOSHAPE', 'SORTABLE'],
            ],
            'with sortable - with UNF' => [
                ['field_name', '', AbstractField::SORTABLE_UNF],
                ['field_name', 'GEOSHAPE', 'SORTABLE', 'UNF'],
            ],
            'with NOINDEX modifier' => [
                ['field_name', '', AbstractField::NOT_SORTABLE, true],
                ['field_name', 'GEOSHAPE', 'NOINDEX'],
            ],
            'with FLAT modifier' => [
                ['field_name', '', AbstractField::NOT_SORTABLE, false, GeoShapeField::COORD_FLAT],
                ['field_name', 'GEOSHAPE', 'FLAT'],
            ],
        ];
    }
}
