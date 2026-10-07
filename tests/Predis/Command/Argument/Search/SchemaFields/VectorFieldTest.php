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

    /**
     * COMPRESSION SQ8 and TRAINING_THRESHOLD (Redis 8.12+) are plain
     * name-value HNSW attributes. An explicit zero threshold is meaningful
     * (it disables mean normalization), so it must reach the server as-is.
     *
     * @dataProvider sq8CompressionProvider
     * @return void
     */
    public function testReturnsCorrectFieldArgumentsArrayWithSq8Compression(array $attributes, array $expected): void
    {
        $this->assertSame($expected, (new VectorField('field_name', 'HNSW', $attributes))->toArray());
    }

    public function sq8CompressionProvider(): array
    {
        return [
            'with COMPRESSION and TRAINING_THRESHOLD' => [
                ['TYPE', 'FLOAT32', 'DIM', 64, 'DISTANCE_METRIC', 'L2', 'COMPRESSION', 'SQ8', 'TRAINING_THRESHOLD', 4096],
                ['field_name', 'VECTOR', 'HNSW', 10, 'TYPE', 'FLOAT32', 'DIM', 64, 'DISTANCE_METRIC', 'L2', 'COMPRESSION', 'SQ8', 'TRAINING_THRESHOLD', 4096],
            ],
            'with COMPRESSION only' => [
                ['TYPE', 'FLOAT16', 'DIM', 64, 'DISTANCE_METRIC', 'COSINE', 'COMPRESSION', 'SQ8'],
                ['field_name', 'VECTOR', 'HNSW', 8, 'TYPE', 'FLOAT16', 'DIM', 64, 'DISTANCE_METRIC', 'COSINE', 'COMPRESSION', 'SQ8'],
            ],
            'with explicit zero TRAINING_THRESHOLD' => [
                ['TYPE', 'FLOAT32', 'DIM', 64, 'DISTANCE_METRIC', 'L2', 'COMPRESSION', 'SQ8', 'TRAINING_THRESHOLD', 0],
                ['field_name', 'VECTOR', 'HNSW', 10, 'TYPE', 'FLOAT32', 'DIM', 64, 'DISTANCE_METRIC', 'L2', 'COMPRESSION', 'SQ8', 'TRAINING_THRESHOLD', 0],
            ],
        ];
    }
}
