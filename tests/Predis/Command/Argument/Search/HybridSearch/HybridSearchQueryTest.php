<?php

namespace Predis\Command\Argument\Search\HybridSearch;

use PHPUnit\Framework\TestCase;
use Predis\Command\Argument\Search\HybridSearch\Combine\LinearCombineConfig;
use Predis\Command\Argument\Search\HybridSearch\Combine\RRFCombineConfig;
use Predis\Command\Argument\Search\HybridSearch\VectorSearch\KNNVectorSearchConfig;
use Predis\Command\Argument\Search\HybridSearch\VectorSearch\RangeVectorSearchConfig;

class HybridSearchQueryTest extends TestCase
{
    /**
     * @dataProvider argumentsProvider
     * @return void
     */
    public function testToArray(HybridSearchQuery $config, array $expectedReturn)
    {
        $this->assertSame($expectedReturn, $config->toArray());
    }

    public function argumentsProvider(): array
    {
        return [
            'with default configs' => [
                (new HybridSearchQuery())
                    ->buildSearchConfig(function (SearchConfig $config) {
                        $config->buildScorerConfig(function (ScorerConfig $scorerConfig) {
                            $scorerConfig->type(ScorerConfig::TYPE_DISMAX);
                        })
                        ->query('*');
                    })
                    ->buildVectorSearchConfig(function (KNNVectorSearchConfig $config) {
                        $config
                            ->vector('vector', [0.1, 0.2, 0.3])
                            ->k(5)
                            ->ef(10);
                    }),
                ['SEARCH', '*', 'SCORER', ScorerConfig::TYPE_DISMAX, 'VSIM', 'vector', 0.1, 0.2, 0.3, 'KNN', 4, 'K', 5, 'EF_RUNTIME', 10]
            ],
            'with RANGE vector search' => [
                (new HybridSearchQuery(RangeVectorSearchConfig::class))
                    ->buildSearchConfig(function (SearchConfig $config) {
                        $config->buildScorerConfig(function (ScorerConfig $scorerConfig) {
                            $scorerConfig->type(ScorerConfig::TYPE_DISMAX);
                        })
                            ->query('*');
                    })
                    ->buildVectorSearchConfig(function (RangeVectorSearchConfig $config) {
                        $config
                            ->vector('vector', [0.1, 0.2, 0.3])
                            ->radius(5)
                            ->epsilon(0.2);
                    }),
                ['SEARCH', '*', 'SCORER', ScorerConfig::TYPE_DISMAX, 'VSIM', 'vector', 0.1, 0.2, 0.3, 'RANGE', 4, 'RADIUS', 5, 'EPSILON', 0.2]
            ],
            'with COMBINE config - RRF' => [
                (new HybridSearchQuery())
                    ->buildSearchConfig(function (SearchConfig $config) {
                        $config->buildScorerConfig(function (ScorerConfig $scorerConfig) {
                            $scorerConfig->type(ScorerConfig::TYPE_DISMAX);
                        })
                            ->query('*');
                    })
                    ->buildVectorSearchConfig(function (KNNVectorSearchConfig $config) {
                        $config
                            ->vector('vector', [0.1, 0.2, 0.3])
                            ->k(5)
                            ->ef(10);
                    })
                    ->buildCombineConfig(function (RRFCombineConfig $config) {
                        $config
                            ->window(5)
                            ->rrfConstant(10);
                    }),
                ['SEARCH', '*', 'SCORER', ScorerConfig::TYPE_DISMAX, 'VSIM', 'vector', 0.1, 0.2, 0.3, 'KNN', 4, 'K', 5, 'EF_RUNTIME', 10, 'COMBINE', 'RRF', 4, 'WINDOW', 5, 'CONSTANT', 10]
            ],
            'with COMBINE config - LINEAR' => [
                (new HybridSearchQuery(KNNVectorSearchConfig::class, LinearCombineConfig::class))
                    ->buildSearchConfig(function (SearchConfig $config) {
                        $config->buildScorerConfig(function (ScorerConfig $scorerConfig) {
                            $scorerConfig->type(ScorerConfig::TYPE_DISMAX);
                        })
                            ->query('*');
                    })
                    ->buildVectorSearchConfig(function (KNNVectorSearchConfig $config) {
                        $config
                            ->vector('vector', [0.1, 0.2, 0.3])
                            ->k(5)
                            ->ef(10);
                    })
                    ->buildCombineConfig(function (LinearCombineConfig $config) {
                        $config
                            ->alpha(0.2)
                            ->beta(0.3);
                    }),
                ['SEARCH', '*', 'SCORER', ScorerConfig::TYPE_DISMAX, 'VSIM', 'vector', 0.1, 0.2, 0.3, 'KNN', 4, 'K', 5, 'EF_RUNTIME', 10, 'COMBINE', 'LINEAR', 4, 'ALPHA', 0.2, 'BETA', 0.3]
            ],
            'with LOAD' => [
                (new HybridSearchQuery())
                    ->buildSearchConfig(function (SearchConfig $config) {
                        $config->buildScorerConfig(function (ScorerConfig $scorerConfig) {
                            $scorerConfig->type(ScorerConfig::TYPE_DISMAX);
                        })
                            ->query('*');
                    })
                    ->buildVectorSearchConfig(function (KNNVectorSearchConfig $config) {
                        $config
                            ->vector('vector', [0.1, 0.2, 0.3])
                            ->k(5)
                            ->ef(10);
                    })
                    ->load(['field1', 'field2']),
                ['SEARCH', '*', 'SCORER', ScorerConfig::TYPE_DISMAX, 'VSIM', 'vector', 0.1, 0.2, 0.3, 'KNN', 4, 'K', 5, 'EF_RUNTIME', 10, 'LOAD', 2, 'field1', 'field2']
            ],
            'with GROUPBY' => [
                (new HybridSearchQuery())
                    ->buildSearchConfig(function (SearchConfig $config) {
                        $config->buildScorerConfig(function (ScorerConfig $scorerConfig) {
                            $scorerConfig->type(ScorerConfig::TYPE_DISMAX);
                        })
                            ->query('*');
                    })
                    ->buildVectorSearchConfig(function (KNNVectorSearchConfig $config) {
                        $config
                            ->vector('vector', [0.1, 0.2, 0.3])
                            ->k(5)
                            ->ef(10);
                    })
                    ->groupBy(
                        ['field1', 'field2'],
                        [
                            new Reducer(Reducer::REDUCE_COUNT, ['prop1', 'prop2']),
                            new Reducer(Reducer::REDUCE_MAX, ['prop1', 'prop2'])
                        ]
                    ),
                ['SEARCH', '*', 'SCORER', ScorerConfig::TYPE_DISMAX, 'VSIM', 'vector', 0.1, 0.2, 0.3, 'KNN', 4, 'K', 5, 'EF_RUNTIME', 10, 'GROUPBY', 2, 'field1', 'field2', 'REDUCE', 'COUNT', 2, 'prop1', 'prop2', 'REDUCE', 'MAX', 2, 'prop1', 'prop2']
            ],
            'with APPLY' => [
                (new HybridSearchQuery())
                    ->buildSearchConfig(function (SearchConfig $config) {
                        $config->buildScorerConfig(function (ScorerConfig $scorerConfig) {
                            $scorerConfig->type(ScorerConfig::TYPE_DISMAX);
                        })
                            ->query('*');
                    })
                    ->buildVectorSearchConfig(function (KNNVectorSearchConfig $config) {
                        $config
                            ->vector('vector', [0.1, 0.2, 0.3])
                            ->k(5)
                            ->ef(10);
                    })
                    ->apply('expr', 'field'),
                ['SEARCH', '*', 'SCORER', ScorerConfig::TYPE_DISMAX, 'VSIM', 'vector', 0.1, 0.2, 0.3, 'KNN', 4, 'K', 5, 'EF_RUNTIME', 10, 'APPLY', 'expr', 'AS', 'field']
            ],
            'with SORTBY' => [
                (new HybridSearchQuery())
                    ->buildSearchConfig(function (SearchConfig $config) {
                        $config->buildScorerConfig(function (ScorerConfig $scorerConfig) {
                            $scorerConfig->type(ScorerConfig::TYPE_DISMAX);
                        })
                            ->query('*');
                    })
                    ->buildVectorSearchConfig(function (KNNVectorSearchConfig $config) {
                        $config
                            ->vector('vector', [0.1, 0.2, 0.3])
                            ->k(5)
                            ->ef(10);
                    })
                    ->sortBy('field', 'DESC', true),
                ['SEARCH', '*', 'SCORER', ScorerConfig::TYPE_DISMAX, 'VSIM', 'vector', 0.1, 0.2, 0.3, 'KNN', 4, 'K', 5, 'EF_RUNTIME', 10, 'SORTBY', 'field', 'DESC', 'WITHCOUNT'],
            ],
            'with FILTER' => [
                (new HybridSearchQuery())
                    ->buildSearchConfig(function (SearchConfig $config) {
                        $config->buildScorerConfig(function (ScorerConfig $scorerConfig) {
                            $scorerConfig->type(ScorerConfig::TYPE_DISMAX);
                        })
                            ->query('*');
                    })
                    ->buildVectorSearchConfig(function (KNNVectorSearchConfig $config) {
                        $config
                            ->vector('vector', [0.1, 0.2, 0.3])
                            ->k(5)
                            ->ef(10);
                    })
                    ->filter('expr'),
                ['SEARCH', '*', 'SCORER', ScorerConfig::TYPE_DISMAX, 'VSIM', 'vector', 0.1, 0.2, 0.3, 'KNN', 4, 'K', 5, 'EF_RUNTIME', 10, 'FILTER', 'expr'],
            ],
            'with LIMIT' => [
                (new HybridSearchQuery())
                    ->buildSearchConfig(function (SearchConfig $config) {
                        $config->buildScorerConfig(function (ScorerConfig $scorerConfig) {
                            $scorerConfig->type(ScorerConfig::TYPE_DISMAX);
                        })
                            ->query('*');
                    })
                    ->buildVectorSearchConfig(function (KNNVectorSearchConfig $config) {
                        $config
                            ->vector('vector', [0.1, 0.2, 0.3])
                            ->k(5)
                            ->ef(10);
                    })
                    ->limit(0, 10),
                ['SEARCH', '*', 'SCORER', ScorerConfig::TYPE_DISMAX, 'VSIM', 'vector', 0.1, 0.2, 0.3, 'KNN', 4, 'K', 5, 'EF_RUNTIME', 10, 'LIMIT', 0, 10],
            ],
            'with PARAMS' => [
                (new HybridSearchQuery())
                    ->buildSearchConfig(function (SearchConfig $config) {
                        $config->buildScorerConfig(function (ScorerConfig $scorerConfig) {
                            $scorerConfig->type(ScorerConfig::TYPE_DISMAX);
                        })
                            ->query('*');
                    })
                    ->buildVectorSearchConfig(function (KNNVectorSearchConfig $config) {
                        $config
                            ->vector('vector', [0.1, 0.2, 0.3])
                            ->k(5)
                            ->ef(10);
                    })
                    ->params(['param1' => 'value1', 'param2' => 'value2']),
                ['SEARCH', '*', 'SCORER', ScorerConfig::TYPE_DISMAX, 'VSIM', 'vector', 0.1, 0.2, 0.3, 'KNN', 4, 'K', 5, 'EF_RUNTIME', 10, 'PARAMS', 4, 'param1', 'value1', 'param2', 'value2'],
            ],
            'with EXPLAINSCORE' => [
                (new HybridSearchQuery())
                    ->buildSearchConfig(function (SearchConfig $config) {
                        $config->buildScorerConfig(function (ScorerConfig $scorerConfig) {
                            $scorerConfig->type(ScorerConfig::TYPE_DISMAX);
                        })
                            ->query('*');
                    })
                    ->buildVectorSearchConfig(function (KNNVectorSearchConfig $config) {
                        $config
                            ->vector('vector', [0.1, 0.2, 0.3])
                            ->k(5)
                            ->ef(10);
                    })
                    ->explainScore(),
                ['SEARCH', '*', 'SCORER', ScorerConfig::TYPE_DISMAX, 'VSIM', 'vector', 0.1, 0.2, 0.3, 'KNN', 4, 'K', 5, 'EF_RUNTIME', 10, 'EXPLAINSCORE'],
            ],
            'with TIMEOUT' => [
                (new HybridSearchQuery())
                    ->buildSearchConfig(function (SearchConfig $config) {
                        $config->buildScorerConfig(function (ScorerConfig $scorerConfig) {
                            $scorerConfig->type(ScorerConfig::TYPE_DISMAX);
                        })
                            ->query('*');
                    })
                    ->buildVectorSearchConfig(function (KNNVectorSearchConfig $config) {
                        $config
                            ->vector('vector', [0.1, 0.2, 0.3])
                            ->k(5)
                            ->ef(10);
                    })
                    ->timeout(),
                ['SEARCH', '*', 'SCORER', ScorerConfig::TYPE_DISMAX, 'VSIM', 'vector', 0.1, 0.2, 0.3, 'KNN', 4, 'K', 5, 'EF_RUNTIME', 10, 'TIMEOUT'],
            ],
            'with WITHCURSOR' => [
                (new HybridSearchQuery())
                    ->buildSearchConfig(function (SearchConfig $config) {
                        $config->buildScorerConfig(function (ScorerConfig $scorerConfig) {
                            $scorerConfig->type(ScorerConfig::TYPE_DISMAX);
                        })
                            ->query('*');
                    })
                    ->buildVectorSearchConfig(function (KNNVectorSearchConfig $config) {
                        $config
                            ->vector('vector', [0.1, 0.2, 0.3])
                            ->k(5)
                            ->ef(10);
                    })
                    ->withCursor(10, 10),
                ['SEARCH', '*', 'SCORER', ScorerConfig::TYPE_DISMAX, 'VSIM', 'vector', 0.1, 0.2, 0.3, 'KNN', 4, 'K', 5, 'EF_RUNTIME', 10, 'WITHCURSOR', 'COUNT', 10, 'MAXIDLE', 10],
            ],
        ];
    }
}
