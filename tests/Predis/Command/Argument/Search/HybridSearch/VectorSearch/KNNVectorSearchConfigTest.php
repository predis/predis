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

class KNNVectorSearchConfigTest extends TestCase
{
    /**
     * @dataProvider argumentsProvider
     * @return void
     */
    public function testToArray(KNNVectorSearchConfig $config, array $expectedReturn)
    {
        $this->assertSame($expectedReturn, $config->toArray());
    }

    /**
     * @return void
     */
    public function testToArrayThrowsExceptionOnMissingProperty(): void
    {
        $config = new KNNVectorSearchConfig();

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
            'with vector and K' => [
                (new KNNVectorSearchConfig())
                    ->vector('vector', '$vector')
                    ->k(5),
                ['VSIM', 'vector', '$vector', 'KNN', 2, 'K', 5]],
            'with vector, K and EF_RUNTIME' => [
                (new KNNVectorSearchConfig())
                    ->vector('vector', '$vector')
                    ->k(5)
                    ->ef(5),
                ['VSIM', 'vector', '$vector', 'KNN', 4, 'K', 5, 'EF_RUNTIME', 5]],
            'with vector, K and FILTER' => [
                (new KNNVectorSearchConfig())
                    ->vector('vector', '$vector')
                    ->k(5)
                    ->filter('*'),
                ['VSIM', 'vector', '$vector', 'KNN', 2, 'K', 5, 'FILTER', '*']],
            'with all' => [
                (new KNNVectorSearchConfig())
                    ->vector('vector', '$vector')
                    ->k(5)
                    ->ef(5)
                    ->filter('*')
                    ->as('alias'),
                ['VSIM', 'vector', '$vector', 'KNN', 4, 'K', 5, 'EF_RUNTIME', 5, 'FILTER', '*', 'YIELD_SCORE_AS', 'alias']],
        ];
    }
}
