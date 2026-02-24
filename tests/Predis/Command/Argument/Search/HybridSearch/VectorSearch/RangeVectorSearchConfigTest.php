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

namespace Predis\Command\Argument\Search\HybridSearch\VectorSearch;

use PHPUnit\Framework\TestCase;
use ValueError;

class RangeVectorSearchConfigTest extends TestCase
{
    /**
     * @dataProvider argumentsProvider
     * @return void
     */
    public function testToArray(RangeVectorSearchConfig $config, array $expectedReturn)
    {
        $this->assertSame($expectedReturn, $config->toArray());
    }

    /**
     * @return void
     */
    public function testAs()
    {
        $config = new RangeVectorSearchConfig();

        $this->assertEquals($config, $config->as('alias'));
    }

    /**
     * @return void
     */
    public function testToArrayThrowsExceptionOnMissingProperty(): void
    {
        $config = new RangeVectorSearchConfig();

        $this->expectException(ValueError::class);
        $this->expectExceptionMessage('Vector configuration not specified.');

        $config
            ->filter('filter')
            ->as('alias')
            ->toArray();
    }

    public function argumentsProvider(): array
    {
        return [
            'with vector and RADIUS' => [
                (new RangeVectorSearchConfig())
                    ->vector('vector', '$vector')
                    ->radius(5),
                ['VSIM', 'vector', '$vector', 'RANGE', 2, 'RADIUS', 5]],
            'with vector, RADIUS and EPSILON' => [
                (new RangeVectorSearchConfig())
                    ->vector('vector', '$vector')
                    ->radius(5)
                    ->epsilon(0.2),
                ['VSIM', 'vector', '$vector', 'RANGE', 4, 'RADIUS', 5, 'EPSILON', 0.2]],
            'with vector, RADIUS and FILTER' => [
                (new RangeVectorSearchConfig())
                    ->vector('vector', '$vector')
                    ->radius(5)
                    ->filter('*'),
                ['VSIM', 'vector', '$vector', 'RANGE', 2, 'RADIUS', 5, 'FILTER', '*']],
            'with all arguments' => [
                (new RangeVectorSearchConfig())
                    ->vector('vector', '$vector')
                    ->radius(5)
                    ->epsilon(0.2)
                    ->as('alias')
                    ->filter('*'),
                ['VSIM', 'vector', '$vector', 'RANGE', 4, 'RADIUS', 5, 'EPSILON', 0.2, 'FILTER', '*', 'YIELD_SCORE_AS', 'alias']],
        ];
    }
}
