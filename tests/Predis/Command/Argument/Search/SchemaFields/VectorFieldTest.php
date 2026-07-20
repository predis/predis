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

    /**
     * RERANK is a boolean key-value attribute for HNSW vector fields on
     * disk-backed (Flex / Auto-Tiering) deployments, where it is mandatory.
     * It flows through the generic attributes list as the string
     * "TRUE"/"FALSE", and the attribute-count token accounts for the extra pair.
     *
     * @dataProvider rerankProvider
     * @return void
     */
    public function testReturnsCorrectFieldArgumentsArrayWithRerankAttribute(string $rerank): void
    {
        $this->assertSame(
            ['field_name', 'VECTOR', 'HNSW', 8, 'TYPE', 'FLOAT32', 'DIM', 128, 'DISTANCE_METRIC', 'L2', 'RERANK', $rerank],
            (new VectorField(
                'field_name',
                'HNSW',
                ['TYPE', 'FLOAT32', 'DIM', 128, 'DISTANCE_METRIC', 'L2', 'RERANK', $rerank]
            ))->toArray());
    }

    public function rerankProvider(): array
    {
        return [
            'with RERANK enabled' => ['TRUE'],
            'with RERANK disabled' => ['FALSE'],
        ];
    }
}
