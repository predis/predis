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

namespace Predis\Command\Argument\Search;

use PHPUnit\Framework\TestCase;

class SchemaTest extends TestCase
{
    /**
     * @var Schema
     */
    private $schema;

    protected function setUp(): void
    {
        $this->schema = new Schema();
    }

    /**
     * @dataProvider textFieldsProvider
     * @param  array $arguments
     * @param  array $expectedSchema
     * @return void
     */
    public function testAddsTextFieldToSchemaConfiguration(
        array $arguments,
        array $expectedSchema
    ): void {
        $this->schema->addTextField(...$arguments);

        $this->assertSame($expectedSchema, $this->schema->toArray());
    }

    /**
     * @dataProvider tagFieldsProvider
     * @param  array $arguments
     * @param  array $expectedSchema
     * @return void
     */
    public function testAddsTagFieldToSchemaConfiguration(
        array $arguments,
        array $expectedSchema
    ): void {
        $this->schema->addTagField(...$arguments);

        $this->assertSame($expectedSchema, $this->schema->toArray());
    }

    /**
     * @dataProvider numericFieldsProvider
     * @param  array $arguments
     * @param  array $expectedSchema
     * @return void
     */
    public function testAddsNumericFieldToSchemaConfiguration(
        array $arguments,
        array $expectedSchema
    ): void {
        $this->schema->addNumericField(...$arguments);

        $this->assertSame($expectedSchema, $this->schema->toArray());
    }

    /**
     * @dataProvider geoFieldsProvider
     * @param  array $arguments
     * @param  array $expectedSchema
     * @return void
     */
    public function testAddsGeoFieldToSchemaConfiguration(
        array $arguments,
        array $expectedSchema
    ): void {
        $this->schema->addGeoField(...$arguments);

        $this->assertSame($expectedSchema, $this->schema->toArray());
    }

    /**
     * @return void
     */
    public function testAddsVectorFieldToSchemaConfiguration(): void
    {
        $this->schema->addVectorField('field_name', 'FLAT', ['attribute_name', 'attribute_value']);

        $this->assertSame(
            ['SCHEMA', 'field_name', 'VECTOR', 'FLAT', 2, 'attribute_name', 'attribute_value'],
            $this->schema->toArray()
        );
    }

    /**
     * @return void
     */
    public function testCreatesCorrectSchemaConfigurationOnMethodsChainCall(): void
    {
        $expectedSchema = [
            'SCHEMA', 'text_field', 'AS', 'txtf', 'TEXT', 'SORTABLE', 'NOSTEM', 'WEIGHT', 10,
            'tag_field', 'AS', 'tf', 'TAG', 'SORTABLE', 'SEPARATOR', '.', 'CASESENSITIVE',
            'numeric_field', 'AS', 'nf', 'NUMERIC', 'SORTABLE',
            'geo_field', 'AS', 'gf', 'GEO', 'SORTABLE',
            'vector_field', 'VECTOR', 'FLAT', 2, 'attribute_name', 'attribute_value',
        ];

        $this->schema->addTextField('text_field', 'txtf', true, false, true, '', 10);
        $this->schema->addTagField('tag_field', 'tf', true, false, '.', true);
        $this->schema->addNumericField('numeric_field', 'nf', true);
        $this->schema->addGeoField('geo_field', 'gf', true);
        $this->schema->addVectorField('vector_field', 'FLAT', ['attribute_name', 'attribute_value']);

        $this->assertSame($expectedSchema, $this->schema->toArray());
    }

    public function geoFieldsProvider(): array
    {
        return [
            'with default arguments' => [
                ['field_name'],
                ['SCHEMA', 'field_name', 'GEO'],
            ],
            'with alias' => [
                ['field_name', 'fn'],
                ['SCHEMA', 'field_name', 'AS', 'fn', 'GEO'],
            ],
            'with sortable - no UNF' => [
                ['field_name', '', Schema::SORTABLE],
                ['SCHEMA', 'field_name', 'GEO', 'SORTABLE'],
            ],
            'with sortable - with UNF' => [
                ['field_name', '', Schema::SORTABLE_UNF],
                ['SCHEMA', 'field_name', 'GEO', 'SORTABLE', 'UNF'],
            ],
            'with NOINDEX modifier' => [
                ['field_name', '', Schema::NOT_SORTABLE, true],
                ['SCHEMA', 'field_name', 'GEO', 'NOINDEX'],
            ],
            'with all modifiers' => [
                ['field_name', 'fn', Schema::SORTABLE, true],
                ['SCHEMA', 'field_name', 'AS', 'fn', 'GEO', 'SORTABLE', 'NOINDEX'],
            ],
        ];
    }

    public function numericFieldsProvider(): array
    {
        return [
            'with default arguments' => [
                ['field_name'],
                ['SCHEMA', 'field_name', 'NUMERIC'],
            ],
            'with alias' => [
                ['field_name', 'fn'],
                ['SCHEMA', 'field_name', 'AS', 'fn', 'NUMERIC'],
            ],
            'with sortable - no UNF' => [
                ['field_name', '', Schema::SORTABLE],
                ['SCHEMA', 'field_name', 'NUMERIC', 'SORTABLE'],
            ],
            'with sortable - with UNF' => [
                ['field_name', '', Schema::SORTABLE_UNF],
                ['SCHEMA', 'field_name', 'NUMERIC', 'SORTABLE', 'UNF'],
            ],
            'with NOINDEX modifier' => [
                ['field_name', '', Schema::NOT_SORTABLE, true],
                ['SCHEMA', 'field_name', 'NUMERIC', 'NOINDEX'],
            ],
            'with all modifiers' => [
                ['field_name', 'fn', Schema::SORTABLE, true],
                ['SCHEMA', 'field_name', 'AS', 'fn', 'NUMERIC', 'SORTABLE', 'NOINDEX'],
            ],
        ];
    }

    public function textFieldsProvider(): array
    {
        return [
            'with default arguments' => [
                ['field_name'],
                ['SCHEMA', 'field_name', 'TEXT'],
            ],
            'with alias' => [
                ['field_name', 'fn'],
                ['SCHEMA', 'field_name', 'AS', 'fn', 'TEXT'],
            ],
            'with sortable - no UNF' => [
                ['field_name', '', Schema::SORTABLE],
                ['SCHEMA', 'field_name', 'TEXT', 'SORTABLE'],
            ],
            'with sortable - with UNF' => [
                ['field_name', '', Schema::SORTABLE_UNF],
                ['SCHEMA', 'field_name', 'TEXT', 'SORTABLE', 'UNF'],
            ],
            'with NOINDEX modifier' => [
                ['field_name', '', Schema::NOT_SORTABLE, true],
                ['SCHEMA', 'field_name', 'TEXT', 'NOINDEX'],
            ],
            'with NOSTEM modifier' => [
                ['field_name', '', Schema::NOT_SORTABLE, false, true],
                ['SCHEMA', 'field_name', 'TEXT', 'NOSTEM'],
            ],
            'with PHONETIC matcher' => [
                ['field_name', '', Schema::NOT_SORTABLE, false, false, 'dm:en'],
                ['SCHEMA', 'field_name', 'TEXT', 'PHONETIC', 'dm:en'],
            ],
            'with WEIGHT modifier' => [
                ['field_name', '', Schema::NOT_SORTABLE, false, false, '', 10],
                ['SCHEMA', 'field_name', 'TEXT', 'WEIGHT', 10],
            ],
            'with WITHSUFFIXTRIE modifier' => [
                ['field_name', '', Schema::NOT_SORTABLE, false, false, '', 1, true],
                ['SCHEMA', 'field_name', 'TEXT', 'WITHSUFFIXTRIE'],
            ],
            'with all modifiers' => [
                ['field_name', 'fn', Schema::SORTABLE, true, true, 'dm:en', 10, true],
                ['SCHEMA', 'field_name', 'AS', 'fn', 'TEXT', 'SORTABLE', 'NOINDEX', 'NOSTEM', 'PHONETIC', 'dm:en', 'WEIGHT', 10, 'WITHSUFFIXTRIE'],
            ],
        ];
    }

    public function tagFieldsProvider(): array
    {
        return [
            'with default arguments' => [
                ['field_name'],
                ['SCHEMA', 'field_name', 'TAG'],
            ],
            'with alias' => [
                ['field_name', 'fn'],
                ['SCHEMA', 'field_name', 'AS', 'fn', 'TAG'],
            ],
            'with sortable - no UNF' => [
                ['field_name', '', Schema::SORTABLE],
                ['SCHEMA', 'field_name', 'TAG', 'SORTABLE'],
            ],
            'with sortable - with UNF' => [
                ['field_name', '', Schema::SORTABLE_UNF],
                ['SCHEMA', 'field_name', 'TAG', 'SORTABLE', 'UNF'],
            ],
            'with NOINDEX modifier' => [
                ['field_name', '', Schema::NOT_SORTABLE, true],
                ['SCHEMA', 'field_name', 'TAG', 'NOINDEX'],
            ],
            'with SEPARATOR modifier' => [
                ['field_name', '', Schema::NOT_SORTABLE, false, '.'],
                ['SCHEMA', 'field_name', 'TAG', 'SEPARATOR', '.'],
            ],
            'with CASESENSITIVE modifier' => [
                ['field_name', '', Schema::NOT_SORTABLE, false, ',', true],
                ['SCHEMA', 'field_name', 'TAG', 'CASESENSITIVE'],
            ],
            'with all modifiers' => [
                ['field_name', 'fn', Schema::SORTABLE, true, '.', true],
                ['SCHEMA', 'field_name', 'AS', 'fn', 'TAG', 'SORTABLE', 'NOINDEX', 'SEPARATOR', '.', 'CASESENSITIVE'],
            ],
        ];
    }
}
