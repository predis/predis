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

use Predis\Command\Argument\Search\CreateArguments;
use Predis\Command\Argument\Search\SchemaFields\AbstractField;
use Predis\Command\Argument\Search\SchemaFields\GeoShapeField;
use Predis\Command\Argument\Search\SchemaFields\NumericField;
use Predis\Command\Argument\Search\SchemaFields\TagField;
use Predis\Command\Argument\Search\SchemaFields\TextField;
use Predis\Command\Argument\Search\SchemaFields\VectorField;
use Predis\Command\Argument\Search\SearchArguments;
use Predis\Command\PrefixableCommand;
use Predis\Command\Redis\PredisCommandTestCase;
use Predis\Response\ServerException;

/**
 * @group commands
 * @group realm-stack
 */
class FTSEARCH_Test extends PredisCommandTestCase
{
    /**
     * {@inheritDoc}
     */
    protected function getExpectedCommand(): string
    {
        return FTSEARCH::class;
    }

    /**
     * {@inheritDoc}
     */
    protected function getExpectedId(): string
    {
        return 'FTSEARCH';
    }

    /**
     * @group disconnected
     * @dataProvider argumentsProvider
     */
    public function testFilterArguments(array $actualArguments, array $expectedArguments): void
    {
        $command = $this->getCommand();
        $command->setArguments($actualArguments);

        $this->assertSameValues($expectedArguments, $command->getArguments());
    }

    /**
     * @group disconnected
     */
    public function testPrefixKeys(): void
    {
        /** @var PrefixableCommand $command */
        $command = $this->getCommand();
        $actualArguments = ['arg1'];
        $prefix = 'prefix:';
        $expectedArguments = ['prefix:arg1'];

        $command->setRawArguments($actualArguments);
        $command->prefixKeys($prefix);

        $this->assertSame($expectedArguments, $command->getArguments());
    }

    /**
     * @group connected
     * @group relay-resp3
     * @return void
     * @requiresRediSearchVersion >= 1.0.0
     * @requiresRedisJsonVersion >= 1.0.0
     */
    public function testSearchValuesByJsonIndex(): void
    {
        $redis = $this->getClient();
        $expectedResponse = [1, 'doc:1', ['arr', '[1,2,3]', 'val', 'hello']];

        $jsonResponse = $redis->jsonset(
            'doc:1',
            '$',
            '[{"arr": [1, 2, 3]}, {"val": "hello"}, {"val": "world"}]'
        );
        $this->assertEquals('OK', $jsonResponse);

        $createArguments = new CreateArguments();
        $createArguments->on('json');
        $createArguments->prefix(['doc:']);

        $schema = [new NumericField('$..arr', 'arr'), new TextField('$..val', 'val')];

        $ftCreateResponse = $redis->ftcreate('idx_json', $schema, $createArguments);
        $this->assertEquals('OK', $ftCreateResponse);

        // Timeout to make sure that index created before search performed.
        usleep(10000);

        $ftSearchArguments = new SearchArguments();
        $ftSearchArguments->addReturn(2, 'arr', 'val');

        $actualResponse = $redis->ftsearch('idx_json', '*', $ftSearchArguments);
        $this->assertSame($expectedResponse, $actualResponse);
    }

    /**
     * @group connected
     * @group relay-resp3
     * @return void
     * @requiresRediSearchVersion >= 1.0.0
     */
    public function testSearchValuesByHashIndex(): void
    {
        $redis = $this->getClient();
        $expectedResponse = [1, 'doc:1', ['should_return', 'value1']];

        $hashResponse = $redis->hmset('doc:1', 'field1', 'value1', 'field2', 'value2');
        $this->assertEquals('OK', $hashResponse);

        $ftCreateArguments = new CreateArguments();
        $ftCreateArguments->prefix(['doc:']);

        $schema = [
            new TextField('field1', 'should_return'),
            new TextField('field2', 'should_not_return'),
        ];

        $ftCreateResponse = $redis->ftcreate('idx_hash', $schema, $ftCreateArguments);
        $this->assertEquals('OK', $ftCreateResponse);

        // Timeout to make sure that index created before search performed.
        usleep(10000);

        $ftSearchArguments = new SearchArguments();
        $ftSearchArguments->addReturn(1, 'should_return');

        $actualResponse = $redis->ftsearch('idx_hash', '*', $ftSearchArguments);
        $this->assertSame($expectedResponse, $actualResponse);
    }

    /**
     * @group connected
     * @return void
     * @requiresRediSearchVersion >= 2.8.0
     */
    public function testSearchValuesByHashIndexResp3(): void
    {
        $redis = $this->getResp3Client();

        $hashResponse = $redis->hmset('doc:1', 'field1', 'value1', 'field2', 'value2');
        $this->assertEquals('OK', $hashResponse);

        $ftCreateArguments = new CreateArguments();
        $ftCreateArguments->prefix(['doc:']);

        $schema = [
            new TextField('field1', 'should_return'),
            new TextField('field2', 'should_not_return'),
        ];

        $ftCreateResponse = $redis->ftcreate('idx_hash', $schema, $ftCreateArguments);
        $this->assertEquals('OK', $ftCreateResponse);

        // Timeout to make sure that index created before search performed.
        usleep(10000);

        $ftSearchArguments = new SearchArguments();
        $ftSearchArguments->addReturn(1, 'should_return');

        $actualResponse = $redis->ftsearch('idx_hash', '*', $ftSearchArguments);
        $this->assertNotEmpty($actualResponse);
    }

    /**
     * @group connected
     * @group relay-resp3
     * @return void
     * @requiresRedisVersion >= 7.9.0
     */
    public function testVectorSearchWithDefaultDialect(): void
    {
        $redis = $this->getClient();

        $this->assertEquals('OK', $redis->ftcreate('test', [
            new VectorField(
                'v', 'HNSW',
                ['TYPE', 'FLOAT32', 'DIM', 2, 'DISTANCE_METRIC', 'L2']
            ),
        ]));

        $this->sleep(0.1);

        $redis->hset('a', 'v', 'aaaaaaaa');
        $redis->hset('b', 'v', 'aaaabaaa');
        $redis->hset('c', 'v', 'aaaaabaa');

        $searchArguments = new SearchArguments();
        $searchArguments->params(['vec', 'aaaaaaaa']);

        $ftSearch = new FTSEARCH();
        $ftSearch->setArguments(['test', '*=>[KNN 2 @v $vec]', $searchArguments]);

        $this->assertContains('DIALECT', $ftSearch->getArguments());
        $this->assertContains(2, $ftSearch->getArguments());

        // Check if dialect applied via constructing custom command object.
        $this->assertEquals(2, $redis->executeCommand($ftSearch)[0]);

        // Check if it works via usual client interface.
        $this->assertEquals(
            2,
            $redis->ftsearch('test', '*=>[KNN 2 @v $vec]', $searchArguments)[0]
        );
    }

    /**
     * @group connected
     * @group relay-resp3
     * @return void
     * @requiresRedisVersion >= 7.9.0
     */
    public function testSearchQueryWithDifferentDialects(): void
    {
        $redis = $this->getClient();
        $createArguments = new CreateArguments();
        $createArguments->prefix(['test:']);

        $this->assertEquals('OK', $redis->ftcreate('test', [
            new TextField('name'),
            new TextField('lastname'),
        ], $createArguments));

        $this->sleep(0.1);

        $redis->hset('test:1', 'name', 'James');
        $redis->hset('test:1', 'lastname', 'Brown');

        // Query with default DIALECT 2
        $this->assertEquals(1, $redis->ftsearch('test', '@name: James Brown')[0]);

        $searchArguments = new SearchArguments();
        $searchArguments->dialect(1);

        // Query with explicit DIALECT 1
        $this->assertEquals(
            0,
            $redis->ftsearch('test', '@name: James Brown', $searchArguments)[0]
        );
    }

    /**
     * @group connected
     * @group relay-resp3
     * @return void
     * @requiresRedisVersion >= 7.9.0
     */
    public function testVectorSearchWithInt8Types(): void
    {
        $redis = $this->getClient();

        $this->assertEquals('OK', $redis->ftcreate('test', [
            new VectorField(
                'v', 'HNSW',
                ['TYPE', 'INT8', 'DIM', 2, 'DISTANCE_METRIC', 'L2']
            ),
        ]));

        $this->sleep(0.1);

        $a = [1.5, 10];
        $b = [123, 100];
        $c = [1, 1];

        $redis->hset('a', 'v', pack('c*', ...$a));
        $redis->hset('b', 'v', pack('c*', ...$b));
        $redis->hset('c', 'v', pack('c*', ...$c));

        $searchArguments = new SearchArguments();
        $searchArguments->params(['vec', pack('c*', ...$a)]);

        $this->assertEquals(
            2,
            $redis->ftsearch('test', '*=>[KNN 2 @v $vec]', $searchArguments)[0]
        );
    }

    /**
     * @group connected
     * @group relay-resp3
     * @return void
     * @requiresRedisVersion >= 7.9.0
     */
    public function testVectorSearchWithUInt8Types(): void
    {
        $redis = $this->getClient();

        $this->assertEquals('OK', $redis->ftcreate('test', [
            new VectorField(
                'v', 'HNSW',
                ['TYPE', 'UINT8', 'DIM', 2, 'DISTANCE_METRIC', 'L2']
            ),
        ]));

        $this->sleep(0.1);

        $a = [1.5, 10];
        $b = [123, 100];
        $c = [1, 1];

        $redis->hset('a', 'v', pack('C*', ...$a));
        $redis->hset('b', 'v', pack('C*', ...$b));
        $redis->hset('c', 'v', pack('C*', ...$c));

        $searchArguments = new SearchArguments();
        $searchArguments->params(['vec', pack('C*', ...$a)]);

        $this->assertEquals(
            2,
            $redis->ftsearch('test', '*=>[KNN 2 @v $vec]', $searchArguments)[0]
        );
    }

    /**
     * @group connected
     * @group relay-resp3
     * @return void
     * @requiresRediSearchVersion >= 2.09.00
     */
    public function testSearchHashEmptyValues(): void
    {
        $redis = $this->getClient();

        $hashResponse = $redis->hmset('test:1', ['text_empty' => '']);
        $this->assertEquals('OK', $hashResponse);

        $schema = [
            new TextField(
                'text_empty',
                '',
                false, false, false, '', 1, false, true
            ),
            new TextField(
                'text_not_empty',
                '',
                false, false, false, '', 1, false, false
            ),
        ];

        $createArgs = new CreateArguments();
        $createArgs->prefix(['test:']);

        $ftCreateResponse = $redis->ftcreate('idx', $schema, $createArgs);
        $this->assertEquals('OK', $ftCreateResponse);

        // Timeout to make sure that index created before search performed.
        usleep(10000);

        $searchArgs = new SearchArguments();
        $searchArgs->dialect(4);

        $this->assertSame(
            [1, 'test:1', ['text_empty', '']],
            $redis->ftsearch('idx', '@text_empty:("")', $searchArgs)
        );

        $this->expectException(ServerException::class);

        $redis->ftsearch('idx', '@text_not_empty:("")', $searchArgs);
    }

    /**
     * @group connected
     * @group relay-resp3
     * @return void
     * @requiresRediSearchVersion >= 2.09.00
     * @requiresRedisJsonVersion >= 1.0.0
     */
    public function testSearchJsonEmptyValues(): void
    {
        $redis = $this->getClient();

        $hashResponse = $redis->jsonset('test:1', '$', '{"text_empty":""}');
        $this->assertEquals('OK', $hashResponse);

        $schema = [
            new TextField(
                '$.text_empty',
                'text_empty',
                false, false, false, '', 1, false, true
            ),
            new TextField(
                '$.text_not_empty',
                'text_not_empty',
                false, false, false, '', 1, false, false
            ),
        ];

        $createArgs = new CreateArguments();
        $createArgs->on('JSON');
        $createArgs->prefix(['test:']);

        $ftCreateResponse = $redis->ftcreate('idx', $schema, $createArgs);
        $this->assertEquals('OK', $ftCreateResponse);

        // Timeout to make sure that index created before search performed.
        usleep(10000);

        $searchArgs = new SearchArguments();
        $searchArgs->dialect(4);

        $this->assertSame(
            [1, 'test:1', ['$', '[{"text_empty":""}]']],
            $redis->ftsearch('idx', '@text_empty:("")', $searchArgs)
        );

        $this->expectException(ServerException::class);

        $redis->ftsearch('idx', '@text_not_empty:("")', $searchArgs);
    }

    /**
     * @group connected
     * @group relay-resp3
     * @return void
     * @requiresRediSearchVersion >= 2.09.00
     */
    public function testSearchWithEnhancedMatchingCapabilities(): void
    {
        $redis = $this->getClient();

        $hashResponse = $redis->hmset(
            'test:1', 'uuid', '3d3586fe-0416-4572-8ce', 'email', 'adriano@acme.com.ie', 'num', 5
        );
        $this->assertEquals('OK', $hashResponse);

        $ftCreateArguments = new CreateArguments();
        $ftCreateArguments->prefix(['test:']);

        $schema = [
            new TagField('uuid'),
            new TagField('email'),
            new NumericField('num'),
        ];

        $ftCreateResponse = $redis->ftcreate('idx_hash', $schema, $ftCreateArguments);
        $this->assertEquals('OK', $ftCreateResponse);

        $ftSearchArguments = new SearchArguments();
        $ftSearchArguments->params(['uuid', '3d3586fe-0416-4572-8ce', 'email', 'adriano@acme.com.ie']);
        $ftSearchArguments->dialect(4);

        $actualResponse = $redis->ftsearch(
            'idx_hash', '@uuid:{$uuid}', $ftSearchArguments
        );

        $this->assertSame([
            1, 'test:1',
            ['uuid', '3d3586fe-0416-4572-8ce', 'email', 'adriano@acme.com.ie', 'num', '5']], $actualResponse
        );

        $actualResponse = $redis->ftsearch(
            'idx_hash', '@email:{$email}', $ftSearchArguments
        );

        $this->assertSame([
            1, 'test:1',
            ['uuid', '3d3586fe-0416-4572-8ce', 'email', 'adriano@acme.com.ie', 'num', '5']], $actualResponse
        );

        $actualResponse = $redis->ftsearch(
            'idx_hash', '@num:[5]', $ftSearchArguments
        );

        $this->assertSame([
            1, 'test:1',
            ['uuid', '3d3586fe-0416-4572-8ce', 'email', 'adriano@acme.com.ie', 'num', '5']], $actualResponse
        );
    }

    /**
     * @group connected
     * @group relay-resp3
     * @return void
     * @requiresRediSearchVersion >= 2.09.00
     */
    public function testGeoSearchQueriesIntersectsAndDisjoint(): void
    {
        $redis = $this->getClient();

        $redis->hset('geo:doc_point1', 'g', 'POINT (10 10)');
        $redis->hset('geo:doc_point2', 'g', 'POINT (50 50)');
        $redis->hset('geo:doc_polygon1', 'g', 'POLYGON ((20 20, 25 35, 35 25, 20 20))');
        $redis->hset('geo:doc_polygon2', 'g', 'POLYGON ((60 60, 65 75, 70 70, 65 55, 60 60))');

        $ftCreateArguments = new CreateArguments();
        $ftCreateArguments->prefix(['geo:']);

        $schema = [
            new GeoShapeField('g', '', AbstractField::NOT_SORTABLE, false, GeoShapeField::COORD_FLAT),
        ];

        $ftCreateResponse = $redis->ftcreate('idx_geo', $schema, $ftCreateArguments);
        $this->assertEquals('OK', $ftCreateResponse);

        // Timeout to make sure that index created before search performed.
        usleep(10000);

        $ftSearchArguments = new SearchArguments();
        $ftSearchArguments->params(['shape', 'POLYGON ((15 15, 75 15, 50 70, 20 40, 15 15))']);
        $ftSearchArguments->noContent();
        $ftSearchArguments->dialect(3);

        $actualResponse = $redis->ftsearch('idx_geo', '@g:[intersects $shape]', $ftSearchArguments);
        $this->assertSameValues(
            [
                2,
                'geo:doc_polygon1',
                'geo:doc_point2',
            ], $actualResponse
        );

        $actualResponse = $redis->ftsearch('idx_geo', '@g:[disjoint $shape]', $ftSearchArguments);
        $this->assertSameValues(
            [
                2,
                'geo:doc_polygon2',
                'geo:doc_point1',
            ], $actualResponse
        );
    }

    /**
     * @group connected
     * @group relay-resp3
     * @return void
     * @requiresRediSearchVersion >= 2.09.00
     */
    public function testGeoSearchQueriesContainsAndWithin(): void
    {
        $redis = $this->getClient();

        $redis->hset('geo:doc_point1', 'g', 'POINT (10 10)');
        $redis->hset('geo:doc_point2', 'g', 'POINT (50 50)');
        $redis->hset('geo:doc_polygon1', 'g', 'POLYGON ((20 20, 25 35, 35 25, 20 20))');
        $redis->hset('geo:doc_polygon2', 'g', 'POLYGON ((60 60, 65 75, 70 70, 65 55, 60 60))');

        $ftCreateArguments = new CreateArguments();
        $ftCreateArguments->prefix(['geo:']);

        $schema = [
            new GeoShapeField('g', '',
                AbstractField::NOT_SORTABLE, false, GeoShapeField::COORD_FLAT
            ),
        ];

        $ftCreateResponse = $redis->ftcreate('idx_geo', $schema, $ftCreateArguments);
        $this->assertEquals('OK', $ftCreateResponse);

        // Timeout to make sure that index created before search performed.
        usleep(10000);

        $ftSearchArguments = new SearchArguments();
        $ftSearchArguments->params(['shape', 'POINT(25 25)']);
        $ftSearchArguments->noContent();
        $ftSearchArguments->dialect(3);

        $actualResponse = $redis->ftsearch('idx_geo', '@g:[contains $shape]', $ftSearchArguments);
        $this->assertSameValues(
            [
                1,
                'geo:doc_polygon1',
            ], $actualResponse
        );

        $ftSearchArguments = new SearchArguments();
        $ftSearchArguments->params(['shape', 'POLYGON((24 24, 24 26, 25 25, 24 24))']);
        $ftSearchArguments->noContent();
        $ftSearchArguments->dialect(3);

        $actualResponse = $redis->ftsearch('idx_geo', '@g:[contains $shape]', $ftSearchArguments);
        $this->assertSameValues(
            [
                1,
                'geo:doc_polygon1',
            ], $actualResponse
        );

        $ftSearchArguments = new SearchArguments();
        $ftSearchArguments->params(['shape', 'POLYGON((15 15, 75 15, 50 70, 20 40, 15 15))']);
        $ftSearchArguments->noContent();
        $ftSearchArguments->dialect(3);

        $actualResponse = $redis->ftsearch('idx_geo', '@g:[within $shape]', $ftSearchArguments);
        $this->assertSameValues(
            [
                2,
                'geo:doc_polygon1',
                'geo:doc_point2',
            ], $actualResponse
        );
    }

    public function argumentsProvider(): array
    {
        return [
            'with NOCONTENT modifier' => [
                ['index', '*', (new SearchArguments())->noContent()],
                ['index', '*', 'NOCONTENT', 'DIALECT', 2],
            ],
            'with VERBATIM modifier' => [
                ['index', '*', (new SearchArguments())->verbatim()],
                ['index', '*', 'VERBATIM', 'DIALECT', 2],
            ],
            'with WITHSCORES modifier' => [
                ['index', '*', (new SearchArguments())->withScores()],
                ['index', '*', 'WITHSCORES', 'DIALECT', 2],
            ],
            'with WITHPAYLOADS modifier' => [
                ['index', '*', (new SearchArguments())->withPayloads()],
                ['index', '*', 'WITHPAYLOADS', 'DIALECT', 2],
            ],
            'with WITHSORTKEYS modifier' => [
                ['index', '*', (new SearchArguments())->withSortKeys()],
                ['index', '*', 'WITHSORTKEYS', 'DIALECT', 2],
            ],
            'with FILTER modifier' => [
                ['index', '*', (new SearchArguments())->searchFilter(['numeric_field', 1, 10])],
                ['index', '*', 'FILTER', 'numeric_field', 1, 10, 'DIALECT', 2],
            ],
            'with GEOFILTER modifier' => [
                ['index', '*', (new SearchArguments())->geoFilter(['geo_field', 12.213, 14.212, 300, 'km'])],
                ['index', '*', 'GEOFILTER', 'geo_field', 12.213, 14.212, 300, 'km', 'DIALECT', 2],
            ],
            'with INKEYS modifier' => [
                ['index', '*', (new SearchArguments())->inKeys(['key1', 'key2'])],
                ['index', '*', 'INKEYS', 2, 'key1', 'key2', 'DIALECT', 2],
            ],
            'with INFIELDS modifier' => [
                ['index', '*', (new SearchArguments())->inFields(['field1', 'field2'])],
                ['index', '*', 'INFIELDS', 2, 'field1', 'field2', 'DIALECT', 2],
            ],
            'with RETURN modifier' => [
                ['index', '*', (new SearchArguments())->addReturn(2, 'identifier', true, 'property')],
                ['index', '*', 'RETURN', 2, 'identifier', 'AS', 'property', 'DIALECT', 2],
            ],
            'with SUMMARIZE modifier' => [
                ['index', '*', (new SearchArguments())->summarize(['field1', 'field2'], 2, 2, ',')],
                ['index', '*', 'SUMMARIZE', 'FIELDS', 2, 'field1', 'field2', 'FRAGS', 2, 'LEN', 2, 'SEPARATOR', ',', 'DIALECT', 2],
            ],
            'with HIGHLIGHT modifier' => [
                ['index', '*', (new SearchArguments())->highlight(['field1', 'field2'], 'openTag', 'closeTag')],
                ['index', '*', 'HIGHLIGHT', 'FIELDS', 2, 'field1', 'field2', 'TAGS', 'openTag', 'closeTag', 'DIALECT', 2],
            ],
            'with SLOP modifier' => [
                ['index', '*', (new SearchArguments())->slop(2)],
                ['index', '*', 'SLOP', 2, 'DIALECT', 2],
            ],
            'with TIMEOUT modifier' => [
                ['index', '*', (new SearchArguments())->timeout(2)],
                ['index', '*', 'TIMEOUT', 2, 'DIALECT', 2],
            ],
            'with INORDER modifier' => [
                ['index', '*', (new SearchArguments())->inOrder()],
                ['index', '*', 'INORDER', 'DIALECT', 2],
            ],
            'with EXPANDER modifier' => [
                ['index', '*', (new SearchArguments())->expander('expander')],
                ['index', '*', 'EXPANDER', 'expander', 'DIALECT', 2],
            ],
            'with SCORER modifier' => [
                ['index', '*', (new SearchArguments())->scorer('scorer')],
                ['index', '*', 'SCORER', 'scorer', 'DIALECT', 2],
            ],
            'with EXPLAINSCORE modifier' => [
                ['index', '*', (new SearchArguments())->explainScore()],
                ['index', '*', 'EXPLAINSCORE', 'DIALECT', 2],
            ],
            'with PAYLOAD modifier' => [
                ['index', '*', (new SearchArguments())->payload('payload')],
                ['index', '*', 'PAYLOAD', 'payload', 'DIALECT', 2],
            ],
            'with SORTBY modifier' => [
                ['index', '*', (new SearchArguments())->sortBy('sort_attribute', 'desc')],
                ['index', '*', 'SORTBY', 'sort_attribute', 'DESC', 'DIALECT', 2],
            ],
            'with LIMIT modifier' => [
                ['index', '*', (new SearchArguments())->limit(2, 2)],
                ['index', '*', 'LIMIT', 2, 2, 'DIALECT', 2],
            ],
            'with PARAMS modifier' => [
                ['index', '*', (new SearchArguments())->params(['name1', 'value2', 'name2', 'value2'])],
                ['index', '*', 'PARAMS', 4, 'name1', 'value2', 'name2', 'value2', 'DIALECT', 2],
            ],
            'with DIALECT modifier' => [
                ['index', '*', (new SearchArguments())->dialect('dialect')],
                ['index', '*', 'DIALECT', 'dialect'],
            ],
            'with chain of arguments' => [
                ['index', '*', (new SearchArguments())->withScores()->withPayloads()->searchFilter(['numeric_field', 1, 10])->addReturn(2, 'identifier', true, 'property')],
                ['index', '*', 'WITHSCORES', 'WITHPAYLOADS', 'FILTER', 'numeric_field', 1, 10, 'RETURN', 2, 'identifier', 'AS', 'property', 'DIALECT', 2],
            ],
        ];
    }
}
