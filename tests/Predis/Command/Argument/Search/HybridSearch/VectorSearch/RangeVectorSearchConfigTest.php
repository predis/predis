<?php

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
    public function testToArrayThrowsExceptionOnMissingProperty(): void
    {
        $config = new RangeVectorSearchConfig();

        $this->expectException(ValueError::class);
        $this->expectExceptionMessage('Radius is a required argument');

        $config
            ->vector('vector', [0.1, 0.2, 0.3])
            ->epsilon(5)
            ->filter('filter')
            ->as('alias')
            ->toArray();

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
                    ->vector('vector', [0.1, 0.2, 0.3])
                    ->radius(5),
                ['VSIM', 'vector', 0.1, 0.2, 0.3, 'RANGE', 2, 'RADIUS', 5]],
            'with vector, RADIUS and EPSILON' => [
                (new RangeVectorSearchConfig())
                    ->vector('vector', [0.1, 0.2, 0.3])
                    ->radius(5)
                    ->epsilon(0.2),
                ['VSIM', 'vector', 0.1, 0.2, 0.3, 'RANGE', 4, 'RADIUS', 5, 'EPSILON', 0.2]],
            'with vector, RADIUS and FILTER' => [
                (new RangeVectorSearchConfig())
                    ->vector('vector', [0.1, 0.2, 0.3])
                    ->radius(5)
                    ->filter('*'),
                ['VSIM', 'vector', 0.1, 0.2, 0.3, 'RANGE', 2, 'RADIUS', 5, 'FILTER', '*']],
            'with all arguments' => [
                (new RangeVectorSearchConfig())
                    ->vector('vector', [0.1, 0.2, 0.3])
                    ->radius(5)
                    ->epsilon(0.2)
                    ->as('alias')
                    ->filter('*'),
                ['VSIM', 'vector', 0.1, 0.2, 0.3, 'RANGE', 4, 'RADIUS', 5, 'EPSILON', 0.2, 'FILTER', '*', 'YIELD_SCORE_AS', 'alias']],
        ];
    }
}
