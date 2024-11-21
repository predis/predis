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

class VectorFieldTest extends TestCase
{
    /**
     * @return void
     */
    public function testReturnsCorrectFieldArgumentsArray(): void
    {
        $this->assertSame(
            ['field_name', 'AS', 'field', 'VECTOR', 'FLAT', 2, 'attribute_name', 'attribute_value'],
            (new VectorField('field_name', 'FLAT', ['attribute_name', 'attribute_value'], 'field'))->toArray());
    }
}
