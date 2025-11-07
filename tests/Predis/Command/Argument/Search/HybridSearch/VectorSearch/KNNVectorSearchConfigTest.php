<?php

namespace Predis\Command\Argument\Search\HybridSearch\VectorSearch;

use PHPUnit\Framework\TestCase;
use Predis\Command\Redis\Utils\VectorUtility;
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
                    ->vector('vector', [0.1, 0.2, 0.3])
                    ->k(5),
                ['VSIM', 'vector', VectorUtility::toBlob([0.1, 0.2, 0.3]), 'KNN', 2, 'K', 5]],
            'with vector, K and EF_RUNTIME' => [
                (new KNNVectorSearchConfig())
                    ->vector('vector', [0.1, 0.2, 0.3])
                    ->k(5)
                    ->ef(5),
                ['VSIM', 'vector', VectorUtility::toBlob([0.1, 0.2, 0.3]), 'KNN', 4, 'K', 5, 'EF_RUNTIME', 5]],
            'with vector, K and FILTER' => [
                (new KNNVectorSearchConfig())
                    ->vector('vector', [0.1, 0.2, 0.3])
                    ->k(5)
                    ->filter('*'),
                ['VSIM', 'vector', VectorUtility::toBlob([0.1, 0.2, 0.3]), 'KNN', 2, 'K', 5, 'FILTER', '*']],
            'with all' => [
                (new KNNVectorSearchConfig())
                    ->vector('vector', VectorUtility::toBlob([0.1, 0.2, 0.3]))
                    ->k(5)
                    ->ef(5)
                    ->filter('*')
                    ->as('alias'),
                ['VSIM', 'vector', VectorUtility::toBlob([0.1, 0.2, 0.3]), 'KNN', 6, 'K', 5, 'EF_RUNTIME', 5, 'YIELD_SCORE_AS', 'alias', 'FILTER', '*']],
        ];
    }
}
