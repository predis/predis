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

namespace Predis\Command\Redis\Search;

use Predis\ClientContextInterface;
use Predis\ClientInterface;
use Predis\Command\Argument\Search\CreateArguments;
use Predis\Command\Argument\Search\HybridSearch\Combine\LinearCombineConfig;
use Predis\Command\Argument\Search\HybridSearch\Combine\RRFCombineConfig;
use Predis\Command\Argument\Search\HybridSearch\HybridSearchQuery;
use Predis\Command\Argument\Search\HybridSearch\Reducer;
use Predis\Command\Argument\Search\HybridSearch\ScorerConfig;
use Predis\Command\Argument\Search\HybridSearch\SearchConfig;
use Predis\Command\Argument\Search\HybridSearch\VectorSearch\KNNVectorSearchConfig;
use Predis\Command\Argument\Search\HybridSearch\VectorSearch\RangeVectorSearchConfig;
use Predis\Command\Argument\Search\SchemaFields\NumericField;
use Predis\Command\Argument\Search\SchemaFields\TagField;
use Predis\Command\Argument\Search\SchemaFields\TextField;
use Predis\Command\Argument\Search\SchemaFields\VectorField;
use Predis\Command\Redis\PredisCommandTestCase;
use Predis\Command\Redis\Utils\VectorUtility;

/**
 * @group commands
 * @group realm-stack
 */
class FTHYBRID_Test extends PredisCommandTestCase
{
    protected function getExpectedCommand(): string
    {
        return FTHYBRID::class;
    }

    protected function getExpectedId(): string
    {
        return 'FTHYBRID';
    }

    /**
     * @group disconnected
     */
    public function testFilterArguments(): void
    {
        $command = $this->getCommand();
        $query = (new HybridSearchQuery())
            ->buildSearchConfig(function (SearchConfig $config) {
                $config->buildScorerConfig(function (ScorerConfig $scorerConfig) {
                    $scorerConfig->type(ScorerConfig::TYPE_DISMAX);
                })
                    ->query('*');
            })
            ->buildVectorSearchConfig(function (KNNVectorSearchConfig $config) {
                $config
                    ->vector('vector', '$vector')
                    ->k(5)
                    ->ef(10);
            })
            ->params([
                'vector' => VectorUtility::toBlob([0.1, 0.2, 0.3]),
            ]);
        $index = 'idx';

        $command->setArguments([$index, $query]);

        $this->assertSameValues(
            ['idx', 'SEARCH', '*', 'SCORER', ScorerConfig::TYPE_DISMAX, 'VSIM', 'vector', '$vector', 'KNN', 4, 'K', 5, 'EF_RUNTIME', 10, 'PARAMS', 2, 'vector', VectorUtility::toBlob([0.1, 0.2, 0.3])],
            $command->getArguments()
        );
    }

    /**
     * @return void
     */
    public function testParseResponse(): void
    {
        $response = ['total_results', 5, 'results', [['key', 'value'], ['key', 'value']]];
        $command = new FTHYBRID();

        $this->assertEquals([
            'total_results' => 5,
            'results' => [['key' => 'value'], ['key' => 'value']],
        ], $command->parseResponse($response));
    }

    /**
     * @group connected
     * @group relay-resp3
     * @requiresRedisVersion >= 8.3.224
     * @return void
     */
    public function testReviewFeedbackHybridSearch()
    {
        $redis = $this->getClient();
        $this->createHybridSearchIndex($redis);
        $this->generateData($redis, 5);

        $query = (new HybridSearchQuery())
            ->buildSearchConfig(function (SearchConfig $config) {
                $config
                    ->query('@color:{red} @color:{green}')
                    ->buildScorerConfig(function (ScorerConfig $scorerConfig) {
                        $scorerConfig->type(ScorerConfig::TYPE_TFIDF);
                    });
            })
            ->buildVectorSearchConfig(function (KNNVectorSearchConfig $config) {
                $config->vector('@embedding', '$vector');
            })
            ->params([
                'vector' => VectorUtility::toBlob([-100, -200, -200, -300]),
            ]);

        $this->assertGreaterThan(0, $redis->fthybrid('idx', $query)['total_results']);
    }

    /**
     * @group connected
     * @group relay-resp3
     * @requiresRedisVersion >= 8.3.224
     * @return void
     */
    public function testReviewFeedbackHybridSearchResp3()
    {
        $redis = $this->getResp3Client();
        $this->createHybridSearchIndex($redis);
        $this->generateData($redis, 5);

        $query = (new HybridSearchQuery())
            ->buildSearchConfig(function (SearchConfig $config) {
                $config
                    ->query('@color:{red} @color:{green}')
                    ->buildScorerConfig(function (ScorerConfig $scorerConfig) {
                        $scorerConfig->type(ScorerConfig::TYPE_TFIDF);
                    });
            })
            ->buildVectorSearchConfig(function (KNNVectorSearchConfig $config) {
                $config->vector('@embedding', '$vector');
            })
            ->params([
                'vector' => VectorUtility::toBlob([-100, -200, -200, -300]),
            ]);

        $this->assertGreaterThan(0, $redis->fthybrid('idx', $query)['total_results']);
    }

    /**
     * @group connected
     * @group relay-resp3
     * @requiresRedisVersion >= 8.3.224
     * @return void
     */
    public function testDefaultHybridSearch()
    {
        $redis = $this->getClient();
        $this->createHybridSearchIndex($redis);
        $this->generateData($redis, 5);

        $query = (new HybridSearchQuery())
            ->buildSearchConfig(function (SearchConfig $config) {
                $config
                    ->query('@color:{red} @color:{green}');
            })
            ->buildVectorSearchConfig(function (KNNVectorSearchConfig $config) {
                $config->vector('@embedding', '$vector');
            })
            ->params([
                'vector' => VectorUtility::toBlob([-100, -200, -200, -300]),
            ]);

        $response = $redis->fthybrid('idx', $query);

        $this->assertEquals(10, $response['total_results']);
        $this->assertCount(10, $response['results']);
        $this->assertEmpty($response['warnings']);
        $this->assertGreaterThan(0, $response['execution_time']);
    }

    /**
     * @group connected
     * @group relay-resp3
     * @requiresRedisVersion >= 8.3.224
     * @return void
     */
    public function testDefaultHybridSearchResp3()
    {
        $redis = $this->getResp3Client();
        $this->createHybridSearchIndex($redis);
        $this->generateData($redis, 5);

        $query = (new HybridSearchQuery())
            ->buildSearchConfig(function (SearchConfig $config) {
                $config
                    ->query('@color:{red} @color:{green}');
            })
            ->buildVectorSearchConfig(function (KNNVectorSearchConfig $config) {
                $config->vector('@embedding', '$vector');
            })
            ->params([
                'vector' => VectorUtility::toBlob([-100, -200, -200, -300]),
            ]);

        $response = $redis->fthybrid('idx', $query);

        $this->assertEquals(10, $response['total_results']);
        $this->assertCount(10, $response['results']);
        $this->assertEmpty($response['warnings']);
        $this->assertGreaterThan(0, $response['execution_time']);
    }

    /**
     * @group connected
     * @group relay-resp3
     * @requiresRedisVersion >= 8.3.224
     * @return void
     */
    public function testHybridSearchQueryWithScorer()
    {
        $redis = $this->getClient();
        $this->createHybridSearchIndex($redis);
        $this->generateData($redis, 10);

        $query = (new HybridSearchQuery(KNNVectorSearchConfig::class, LinearCombineConfig::class))
            ->buildSearchConfig(function (SearchConfig $config) {
                $config
                    ->query('shoes')
                    ->buildScorerConfig(function (ScorerConfig $scorerConfig) {
                        $scorerConfig->type(ScorerConfig::TYPE_TFIDF);
                    });
            })
            ->buildVectorSearchConfig(function (KNNVectorSearchConfig $config) {
                $config->vector('@embedding', '$vector');
            })
            ->buildCombineConfig(function (LinearCombineConfig $config) {
                $config
                    ->alpha(1)
                    ->beta(0);
            })
            ->load(['@description', '@color', '@price', '@size', '@__score', '@__item'])
            ->limit(0, 2)
            ->params([
                'vector' => VectorUtility::toBlob([1, 2, 2, 3]),
            ]);

        $expectedResultsTFIDF = [
            [
                'description' => 'red shoes',
                'color' => 'red',
                'price' => '15',
                'size' => '10',
                '__score' => '2',
            ],
            [
                'description' => 'green shoes with red laces',
                'color' => 'green',
                'price' => '16',
                'size' => '11',
                '__score' => '2',
            ],
        ];

        $response = $redis->fthybrid('idx', $query);
        $this->assertEquals($expectedResultsTFIDF, $response['results']);

        $query = (new HybridSearchQuery(KNNVectorSearchConfig::class, LinearCombineConfig::class))
            ->buildSearchConfig(function (SearchConfig $config) {
                $config
                    ->query('shoes')
                    ->buildScorerConfig(function (ScorerConfig $scorerConfig) {
                        $scorerConfig->type(ScorerConfig::TYPE_BM25);
                    });
            })
            ->buildVectorSearchConfig(function (KNNVectorSearchConfig $config) {
                $config->vector('@embedding', '$vector');
            })
            ->buildCombineConfig(function (LinearCombineConfig $config) {
                $config
                    ->alpha(1)
                    ->beta(0);
            })
            ->load(['@description', '@color', '@price', '@size', '@__score', '@__item'])
            ->limit(0, 2)
            ->params([
                'vector' => VectorUtility::toBlob([1, 2, 2, 3]),
            ]);

        $expectedResultsBM25 = [
            [
                'description' => 'red shoes',
                'color' => 'red',
                'price' => '15',
                'size' => '10',
                '__score' => '0.657894719299',
            ],
            [
                'description' => 'green shoes with red laces',
                'color' => 'green',
                'price' => '16',
                'size' => '11',
                '__score' => '0.657894719299',
            ],
        ];

        $response = $redis->fthybrid('idx', $query);
        $this->assertEquals($expectedResultsBM25, $response['results']);
    }

    /**
     * @group connected
     * @group relay-resp3
     * @requiresRedisVersion >= 8.3.224
     * @return void
     */
    public function testHybridSearchQueryWithVsimMethodDefinedQueryInit()
    {
        $redis = $this->getClient();
        $this->createHybridSearchIndex($redis);
        $this->generateData($redis, 5);

        $query = (new HybridSearchQuery())
            ->buildSearchConfig(function (SearchConfig $config) {
                $config
                    ->query('shoes');
            })
            ->buildVectorSearchConfig(function (KNNVectorSearchConfig $config) {
                $config
                    ->vector('@embedding-hnsw', '$vector')
                    ->k(3)
                    ->ef(1);
            })
            ->params([
                'vector' => 'abcd1234efgh5678',
            ]);
        $response = $redis->fthybrid('idx', $query);
        $this->assertGreaterThan(0, count($response['results']));
    }

    /**
     * @group connected
     * @group relay-resp3
     * @requiresRedisVersion >= 8.3.224
     * @return void
     */
    public function testHybridSearchQueryWithVsimFilter()
    {
        $redis = $this->getClient();
        $this->createHybridSearchIndex($redis);
        $this->generateData($redis, 5);

        $query = (new HybridSearchQuery())
            ->buildSearchConfig(function (SearchConfig $config) {
                $config
                    ->query('@color:{missing}');
            })
            ->buildVectorSearchConfig(function (KNNVectorSearchConfig $config) {
                $config
                    ->vector('@embedding', '$vector')
                    ->filter('@price:[15 16] @size:[10 11]');
            })
            ->load(['@price', '@size'])
            ->params([
                'vector' => 'abcd1234efgh5678',
            ]);

        $response = $redis->fthybrid('idx', $query);
        $this->assertGreaterThan(0, count($response['results']));

        foreach ($response['results'] as $result) {
            $this->assertTrue(in_array($result['price'], ['15', '16']));
            $this->assertTrue(in_array($result['size'], ['10', '11']));
        }
    }

    /**
     * @group connected
     * @group relay-resp3
     * @requiresRedisVersion >= 8.3.224
     * @return void
     */
    public function testHybridSearchQueryWithSearchScoreAliases()
    {
        $redis = $this->getClient();
        $this->createHybridSearchIndex($redis);
        $this->generateData($redis);

        $query = (new HybridSearchQuery())
            ->buildSearchConfig(function (SearchConfig $config) {
                $config
                    ->query('shoes')
                    ->as('search_score');
            })
            ->buildVectorSearchConfig(function (KNNVectorSearchConfig $config) {
                $config
                    ->vector('@embedding', '$vector');
            })
            ->params([
                'vector' => 'abcd1234efgh5678',
            ]);

        $response = $redis->fthybrid('idx', $query);
        $this->assertGreaterThan(0, count($response['results']));

        foreach ($response['results'] as $result) {
            if (in_array($result['__key'], ['item:0', 'item:1', 'item:4'])) {
                $this->assertArrayHasKey('search_score', $result);
                $this->assertArrayHasKey('__score', $result);
            }
        }
    }

    /**
     * @group connected
     * @group relay-resp3
     * @requiresRedisVersion >= 8.3.224
     * @return void
     */
    public function testHybridSearchQueryWithVsimScoreAliases()
    {
        $redis = $this->getClient();
        $this->createHybridSearchIndex($redis);
        $this->generateData($redis);

        $query = (new HybridSearchQuery())
            ->buildSearchConfig(function (SearchConfig $config) {
                $config
                    ->query('shoes');
            })
            ->buildVectorSearchConfig(function (KNNVectorSearchConfig $config) {
                $config
                    ->vector('@embedding-hnsw', '$vector')
                    ->k(3)
                    ->ef(1)
                    ->as('vsim_score');
            })
            ->params([
                'vector' => 'abcd1234efgh5678',
            ]);

        $response = $redis->fthybrid('idx', $query);
        $this->assertGreaterThan(0, count($response['results']));

        foreach ($response['results'] as $result) {
            if (in_array($result['__key'], ['item:0', 'item:1', 'item:2'])) {
                $this->assertArrayHasKey('vsim_score', $result);
                $this->assertArrayHasKey('__score', $result);
            }
        }
    }

    /**
     * @group connected
     * @group relay-resp3
     * @requiresRedisVersion >= 8.3.224
     * @return void
     */
    public function testHybridSearchQueryWithCombineScoreAliases()
    {
        $redis = $this->getClient();
        $this->createHybridSearchIndex($redis);
        $this->generateData($redis);

        $query = (new HybridSearchQuery(KNNVectorSearchConfig::class, LinearCombineConfig::class))
            ->buildSearchConfig(function (SearchConfig $config) {
                $config
                    ->query('shoes')
                    ->as('search_score');
            })
            ->buildVectorSearchConfig(function (KNNVectorSearchConfig $config) {
                $config
                    ->vector('@embedding-hnsw', '$vector')
                    ->k(3)
                    ->ef(1)
                    ->as('vsim_score');
            })
            ->buildCombineConfig(function (LinearCombineConfig $config) {
                $config
                    ->alpha(0.5)
                    ->beta(0.5)
                    ->as('combine_score');
            })
            ->params([
                'vector' => 'abcd1234efgh5678',
            ]);

        $response = $redis->fthybrid('idx', $query);
        $this->assertGreaterThan(0, count($response['results']));

        foreach ($response['results'] as $result) {
            if (in_array($result['__key'], ['item:0', 'item:1', 'item:2'])) {
                $this->assertArrayHasKey('vsim_score', $result);
            } else {
                $this->assertArrayNotHasKey('vsim_score', $result);
            }

            if (in_array($result['__key'], ['item:0', 'item:1', 'item:4'])) {
                $this->assertArrayHasKey('search_score', $result);
            } else {
                $this->assertArrayNotHasKey('search_score', $result);
            }

            $this->assertArrayHasKey('combine_score', $result);
            $this->assertArrayNotHasKey('__score', $result);
        }
    }

    /**
     * @group connected
     * @group relay-resp3
     * @requiresRedisVersion >= 8.3.224
     * @return void
     */
    public function testHybridSearchQueryWithCombineAllScoreAliases()
    {
        $redis = $this->getClient();
        $this->createHybridSearchIndex($redis);
        $this->generateData($redis);

        $query = (new HybridSearchQuery(KNNVectorSearchConfig::class, LinearCombineConfig::class))
            ->buildSearchConfig(function (SearchConfig $config) {
                $config
                    ->query('shoes')
                    ->as('search_score');
            })
            ->buildVectorSearchConfig(function (KNNVectorSearchConfig $config) {
                $config
                    ->vector('@embedding-hnsw', '$vector')
                    ->k(3)
                    ->ef(1)
                    ->as('vsim_score');
            })
            ->buildCombineConfig(function (LinearCombineConfig $config) {
                $config
                    ->alpha(0.5)
                    ->beta(0.5)
                    ->as('combine_score');
            })
            ->params([
                'vector' => 'abcd1234efgh5678',
            ]);

        $response = $redis->fthybrid('idx', $query);
        $this->assertGreaterThan(0, count($response['results']));

        foreach ($response['results'] as $result) {
            $this->assertArrayHasKey('combine_score', $result);
            $this->assertArrayNotHasKey('__score', $result);
        }
    }

    /**
     * @group connected
     * @group relay-resp3
     * @requiresRedisVersion >= 8.3.224
     * @return void
     */
    public function testHybridSearchQueryWithVsimKNN()
    {
        $redis = $this->getClient();
        $this->createHybridSearchIndex($redis);
        $this->generateData($redis, 10);

        $query = (new HybridSearchQuery())
            ->buildSearchConfig(function (SearchConfig $config) {
                $config
                    ->query('@color:{none}');
            })
            ->buildVectorSearchConfig(function (KNNVectorSearchConfig $config) {
                $config
                    ->vector('@embedding', '$vector')
                    ->k(3);
            })
            ->params([
                'vector' => VectorUtility::toBlob([1, 2, 2, 3]),
            ]);

        $expected_results = [
            ['__key' => 'item:2', '__score' => '0.016393442623'],
            ['__key' => 'item:7', '__score' => '0.0161290322581'],
            ['__key' => 'item:12', '__score' => '0.015873015873'],
        ];

        $response = $redis->fthybrid('idx', $query);
        $this->assertEquals($expected_results, $response['results']);

        $query = (new HybridSearchQuery())
            ->buildSearchConfig(function (SearchConfig $config) {
                $config
                    ->query('@color:{none}');
            })
            ->buildVectorSearchConfig(function (KNNVectorSearchConfig $config) {
                $config
                    ->vector('@embedding-hnsw', '$vector')
                    ->k(3)
                    ->ef(1);
            })
            ->params([
                'vector' => VectorUtility::toBlob([1, 2, 2, 3]),
            ]);

        $expected_results = [
            ['__key' => 'item:12', '__score' => '0.016393442623'],
            ['__key' => 'item:22', '__score' => '0.0161290322581'],
            ['__key' => 'item:27', '__score' => '0.015873015873'],
        ];

        $response = $redis->fthybrid('idx', $query);
        $this->assertEquals($expected_results, $response['results']);
    }

    /**
     * @group connected
     * @group relay-resp3
     * @requiresRedisVersion >= 8.3.224
     * @return void
     */
    public function testHybridSearchQueryWithVsimRange()
    {
        $redis = $this->getClient();
        $this->createHybridSearchIndex($redis);
        $this->generateData($redis, 10);

        $query = (new HybridSearchQuery(RangeVectorSearchConfig::class))
            ->buildSearchConfig(function (SearchConfig $config) {
                $config
                    ->query('@color:{none}');
            })
            ->buildVectorSearchConfig(function (RangeVectorSearchConfig $config) {
                $config
                    ->vector('@embedding', '$vector');
            })
            ->limit(0, 3)
            ->params([
                'vector' => VectorUtility::toBlob([1, 2, 7, 6]),
            ]);

        $expected_results = [
            ['__key' => 'item:2', '__score' => '0.016393442623'],
            ['__key' => 'item:7', '__score' => '0.0161290322581'],
            ['__key' => 'item:12', '__score' => '0.015873015873'],
        ];

        $response = $redis->fthybrid('idx', $query);
        $this->assertEquals($expected_results, $response['results']);

        $query = (new HybridSearchQuery(RangeVectorSearchConfig::class))
            ->buildSearchConfig(function (SearchConfig $config) {
                $config
                    ->query('@color:{none}');
            })
            ->buildVectorSearchConfig(function (RangeVectorSearchConfig $config) {
                $config
                    ->vector('@embedding-hnsw', '$vector')
                    ->radius(2)
                    ->epsilon(0.5);
            })
            ->limit(0, 3)
            ->params([
                'vector' => VectorUtility::toBlob([1, 2, 7, 6]),
            ]);

        $expected_results = [
            ['__key' => 'item:27', '__score' => '0.016393442623'],
            ['__key' => 'item:12', '__score' => '0.0161290322581'],
            ['__key' => 'item:22', '__score' => '0.015873015873'],
        ];

        $response = $redis->fthybrid('idx', $query);
        $this->assertEquals($expected_results, $response['results']);
    }

    /**
     * @group connected
     * @group relay-resp3
     * @requiresRedisVersion >= 8.3.224
     * @return void
     */
    public function testHybridSearchQueryWithCombine()
    {
        $redis = $this->getClient();
        $this->createHybridSearchIndex($redis);
        $this->generateData($redis, 10);

        $query = (new HybridSearchQuery(KNNVectorSearchConfig::class, LinearCombineConfig::class))
            ->buildSearchConfig(function (SearchConfig $config) {
                $config
                    ->query('@color:{red}');
            })
            ->buildVectorSearchConfig(function (KNNVectorSearchConfig $config) {
                $config
                    ->vector('@embedding', '$vector');
            })
            ->buildCombineConfig(function (LinearCombineConfig $config) {
                $config
                    ->alpha(0.5)
                    ->beta(0.5);
            })
            ->limit(0, 3)
            ->params([
                'vector' => VectorUtility::toBlob([1, 2, 7, 6]),
            ]);

        $expected_results = [
            ['__key' => 'item:2', '__score' => '0.166666666667'],
            ['__key' => 'item:7', '__score' => '0.166666666667'],
            ['__key' => 'item:12', '__score' => '0.166666666667'],
        ];

        $response = $redis->fthybrid('idx', $query);
        $this->assertEquals($expected_results, $response['results']);

        $query = (new HybridSearchQuery(KNNVectorSearchConfig::class, RRFCombineConfig::class))
            ->buildSearchConfig(function (SearchConfig $config) {
                $config
                    ->query('@color:{red}');
            })
            ->buildVectorSearchConfig(function (KNNVectorSearchConfig $config) {
                $config
                    ->vector('@embedding', '$vector');
            })
            ->buildCombineConfig(function (RRFCombineConfig $config) {
                $config
                    ->window(3)
                    ->rrfConstant(0.5);
            })
            ->limit(0, 3)
            ->params([
                'vector' => VectorUtility::toBlob([1, 2, 7, 6]),
            ]);

        $expected_results = [
            ['__key' => 'item:2', '__score' => '1.5'],
            ['__key' => 'item:0', '__score' => '1'],
            ['__key' => 'item:7', '__score' => '0.5'],
        ];

        $response = $redis->fthybrid('idx', $query);
        $this->assertEquals($expected_results, $response['results']);
    }

    /**
     * @group connected
     * @group relay-resp3
     * @requiresRedisVersion >= 8.3.224
     * @return void
     */
    public function testHybridSearchQueryWithLoad()
    {
        $redis = $this->getClient();
        $this->createHybridSearchIndex($redis);
        $this->generateData($redis, 10);

        $query = (new HybridSearchQuery(KNNVectorSearchConfig::class, LinearCombineConfig::class))
            ->buildSearchConfig(function (SearchConfig $config) {
                $config
                    ->query('@color:{red|green|black}');
            })
            ->buildVectorSearchConfig(function (KNNVectorSearchConfig $config) {
                $config
                    ->vector('@embedding', '$vector');
            })
            ->buildCombineConfig(function (LinearCombineConfig $config) {
                $config
                    ->alpha(0.5)
                    ->beta(0.5);
            })
            ->load(['@description', '@color', '@price', '@size', '@__key'])
            ->limit(0, 1)
            ->params([
                'vector' => VectorUtility::toBlob([1, 2, 7, 6]),
            ]);

        $expected_results = [
            'description' => 'red dress',
            'color' => 'red',
            'price' => '17',
            'size' => '12',
            '__key' => 'item:2',
        ];

        $response = $redis->fthybrid('idx', $query);
        $this->assertEquals($expected_results, $response['results'][0]);
    }

    /**
     * @group connected
     * @group relay-resp3
     * @requiresRedisVersion >= 8.3.224
     * @return void
     */
    public function testHybridSearchQueryWithLoadAndApply()
    {
        $redis = $this->getClient();
        $this->createHybridSearchIndex($redis);
        $this->generateData($redis, 10);

        $query = (new HybridSearchQuery())
            ->buildSearchConfig(function (SearchConfig $config) {
                $config
                    ->query('@color:{red}');
            })
            ->buildVectorSearchConfig(function (KNNVectorSearchConfig $config) {
                $config
                    ->vector('@embedding', '$vector');
            })
            ->load(['@color', '@price', '@size'])
            ->apply([
                'price_discount' => '@price - (@price * 0.1)',
                'tax_discount' => '@price_discount * 0.2',
            ])
            ->limit(0, 3)
            ->params([
                'vector' => VectorUtility::toBlob([1, 2, 7, 6]),
            ]);

        $expected_results = [
            [
                'color' => 'red',
                'price' => '17',
                'size' => '12',
                'price_discount' => '15.3',
                'tax_discount' => '3.06',
            ],
            [
                'color' => 'red',
                'price' => '18',
                'size' => '11',
                'price_discount' => '16.2',
                'tax_discount' => '3.24',
            ],
            [
                'color' => 'red',
                'price' => '15',
                'size' => '10',
                'price_discount' => '13.5',
                'tax_discount' => '2.7',
            ],
        ];

        $response = $redis->fthybrid('idx', $query);
        $this->assertEquals($expected_results, $response['results']);
    }

    /**
     * @group connected
     * @group relay-resp3
     * @requiresRedisVersion >= 8.3.224
     * @return void
     */
    public function testHybridSearchQueryWithLoadAndFilter()
    {
        $redis = $this->getClient();
        $this->createHybridSearchIndex($redis);
        $this->generateData($redis, 10);

        $query = (new HybridSearchQuery())
            ->buildSearchConfig(function (SearchConfig $config) {
                $config
                    ->query('@color:{red|green|black}');
            })
            ->buildVectorSearchConfig(function (KNNVectorSearchConfig $config) {
                $config
                    ->vector('@embedding', '$vector');
            })
            ->load(['@description', '@color', '@price', '@size'])
            ->filter('@price=="15"')
            ->limit(0, 3)
            ->params([
                'vector' => VectorUtility::toBlob([1, 2, 7, 6]),
            ]);

        $response = $redis->fthybrid('idx', $query);
        $this->assertCount(3, $response['results']);

        foreach ($response['results'] as $result) {
            $this->assertEquals(15, $result['price']);
        }
    }

    /**
     * @group connected
     * @group relay-resp3
     * @requiresRedisVersion >= 8.3.224
     * @return void
     */
    public function testHybridSearchQueryWithLoadApplyAndParams()
    {
        $redis = $this->getClient();
        $this->createHybridSearchIndex($redis);
        $this->generateData($redis, 5);

        $query = (new HybridSearchQuery())
            ->buildSearchConfig(function (SearchConfig $config) {
                $config
                    ->query('@color:{$color_criteria}');
            })
            ->buildVectorSearchConfig(function (KNNVectorSearchConfig $config) {
                $config
                    ->vector('@embedding', '$vector');
            })
            ->load(['@description', '@color', '@price'])
            ->apply([
                'price_discount' => '@price - (@price * 0.1)',
            ])
            ->params([
                'vector' => 'abcd1234abcd5678',
                'color_criteria' => 'red',
            ])
            ->limit(0, 3);

        $expected_results = [
            [
                'description' => 'red shoes',
                'color' => 'red',
                'price' => '15',
                'price_discount' => '13.5',
            ],
            [
                'description' => 'red dress',
                'color' => 'red',
                'price' => '17',
                'price_discount' => '15.3',
            ],
            [
                'description' => 'red shoes',
                'color' => 'red',
                'price' => '16',
                'price_discount' => '14.4',
            ],
        ];

        $response = $redis->fthybrid('idx', $query);
        $this->assertEquals($expected_results, $response['results']);
    }

    /**
     * @group connected
     * @group relay-resp3
     * @requiresRedisVersion >= 8.3.224
     * @return void
     */
    public function testHybridSearchQueryWithApplyAndSortBy()
    {
        $redis = $this->getClient();
        $this->createHybridSearchIndex($redis);
        $this->generateData($redis);

        $query = (new HybridSearchQuery())
            ->buildSearchConfig(function (SearchConfig $config) {
                $config
                    ->query('@color:{red|green}');
            })
            ->buildVectorSearchConfig(function (KNNVectorSearchConfig $config) {
                $config
                    ->vector('@embedding', '$vector');
            })
            ->load(['@color', '@price'])
            ->apply([
                'price_discount' => '@price - (@price * 0.1)',
            ])
            ->sortBy([
                '@price_discount' => 'DESC',
                '@color' => 'ASC',
            ])
            ->limit(0, 5)
            ->params([
                'vector' => VectorUtility::toBlob([1, 2, 7, 6]),
            ]);

        $expected_results = [
            [
                'color' => 'orange',
                'price' => '18',
                'price_discount' => '16.2',
            ],
            [
                'color' => 'red',
                'price' => '17',
                'price_discount' => '15.3',
            ],
            [
                'color' => 'green',
                'price' => '16',
                'price_discount' => '14.4',
            ],
            [
                'color' => 'black',
                'price' => '15',
                'price_discount' => '13.5',
            ],
            [
                'color' => 'red',
                'price' => '15',
                'price_discount' => '13.5',
            ],
        ];

        $response = $redis->fthybrid('idx', $query);
        $this->assertEquals($expected_results, $response['results']);
    }

    /**
     * @group connected
     * @group relay-resp3
     * @requiresRedisVersion >= 8.3.224
     * @return void
     */
    public function testHybridSearchQueryWithLoadAndGroupBy()
    {
        $redis = $this->getClient();
        $this->createHybridSearchIndex($redis);
        $this->generateData($redis, 10);

        $query = (new HybridSearchQuery())
            ->buildSearchConfig(function (SearchConfig $config) {
                $config
                    ->query('@color:{red|green}');
            })
            ->buildVectorSearchConfig(function (KNNVectorSearchConfig $config) {
                $config
                    ->vector('@embedding', '$vector');
            })
            ->load(['@color', '@price', '@size', '@item_type'])
            ->groupBy(
                ['@item_type', '@price'],
                [
                    new Reducer(Reducer::REDUCE_COUNT_DISTINCT, ['@color'], 'colors_count'),
                    new Reducer(Reducer::REDUCE_MIN, ['@size']),
                ]
            )
            ->sortBy([
                '@price' => 'ASC',
            ])
            ->limit(0, 4)
            ->params([
                'vector' => VectorUtility::toBlob([1, 2, 7, 6]),
            ]);

        $expected_results = [
            [
                'item_type' => 'dress',
                'price' => '15',
                'colors_count' => '1',
                '__generated_aliasminsize' => '10',
            ],
            [
                'item_type' => 'shoes',
                'price' => '15',
                'colors_count' => '2',
                '__generated_aliasminsize' => '10',
            ],
            [
                'item_type' => 'shoes',
                'price' => '16',
                'colors_count' => '2',
                '__generated_aliasminsize' => '10',
            ],
            [
                'item_type' => 'dress',
                'price' => '16',
                'colors_count' => '1',
                '__generated_aliasminsize' => '11',
            ],
        ];

        $response = $redis->fthybrid('idx', $query);
        $this->assertEquals($expected_results, $response['results']);
    }

    /**
     * @group connected
     * @group relay-resp3
     * @requiresRedisVersion >= 8.3.224
     * @return void
     */
    public function testHybridSearchQueryWithCursor()
    {
        $redis = $this->getClient();
        $this->createHybridSearchIndex($redis);
        $this->generateData($redis, 10);

        $query = (new HybridSearchQuery())
            ->buildSearchConfig(function (SearchConfig $config) {
                $config
                    ->query('@color:{red|green}');
            })
            ->buildVectorSearchConfig(function (KNNVectorSearchConfig $config) {
                $config
                    ->vector('@embedding', '$vector');
            })
            ->withCursor(5, 100)
            ->params([
                'vector' => VectorUtility::toBlob([1, 2, 7, 6]),
            ]);

        $response = $redis->fthybrid('idx', $query);
        $this->assertGreaterThan(0, $response['SEARCH']);
        $this->assertGreaterThan(0, $response['VSIM']);
    }

    protected function createHybridSearchIndex(ClientInterface $client)
    {
        $fields = [
            new TextField('description'),
            new NumericField('price'),
            new TagField('color'),
            new TagField('item_type'),
            new NumericField('size'),
            new VectorField('embedding', 'FLAT',
                ['TYPE', 'FLOAT32', 'DIM', 4, 'DISTANCE_METRIC', 'L2']
            ),
            new VectorField('embedding-hnsw', 'HNSW',
                ['TYPE', 'FLOAT32', 'DIM', 4, 'DISTANCE_METRIC', 'L2']
            ),
        ];

        $arguments = new CreateArguments();
        $arguments->prefix(['item:']);

        $this->assertEquals('OK', $client->ftcreate('idx', $fields, $arguments));
        sleep(0.1);
    }

    protected function generateData(ClientInterface $client, int $itemSets = 1)
    {
        $items = [
            [[1.0, 2.0, 7.0, 8.0], 'red shoes'],
            [[1.0, 4.0, 7.0, 8.0], 'green shoes with red laces'],
            [[1.0, 2.0, 6.0, 5.0], 'red dress'],
            [[2.0, 3.0, 6.0, 5.0], 'orange dress'],
            [[5.0, 6.0, 7.0, 8.0], 'black shoes'],
        ];
        $mergedItems = [];

        for ($i = 0; $i < $itemSets; ++$i) {
            $mergedItems = array_merge($mergedItems, $items);
        }

        $client->pipeline(function (ClientContextInterface $pipe) use ($mergedItems) {
            for ($i = 0; $i < count($mergedItems); ++$i) {
                [$vec, $description] = $mergedItems[$i];

                $pipe->hmset("item:{$i}", [
                    'description' => $description,
                    'embedding' => VectorUtility::toBlob($vec),
                    'embedding-hnsw' => VectorUtility::toBlob($vec),
                    'price' => 15 + $i % 4,
                    'color' => explode(' ', $description)[0],
                    'item_type' => explode(' ', $description)[1],
                    'size' => 10 + $i % 3,
                ]);
            }
        });
    }
}
