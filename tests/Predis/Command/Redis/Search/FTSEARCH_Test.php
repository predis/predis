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

namespace Predis\Command\Redis\Search;

use Predis\Command\Argument\Search\CreateArguments;
use Predis\Command\Argument\Search\SchemaFields\NumericField;
use Predis\Command\Argument\Search\SchemaFields\TextField;
use Predis\Command\Argument\Search\SearchArguments;
use Predis\Command\Redis\PredisCommandTestCase;

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
     * @group connected
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
        usleep(2000);

        $ftSearchArguments = new SearchArguments();
        $ftSearchArguments->addReturn(2, 'arr', 'val');

        $actualResponse = $redis->ftsearch('idx_json', '*', $ftSearchArguments);
        $this->assertSame($expectedResponse, $actualResponse);
    }

    /**
     * @group connected
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
        usleep(2000);

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
        $expectedResponse = [
            'attributes' => [],
            'error' => [],
            'total_results' => 1,
            'format' => 'STRING',
            'results' => [
                ['id' => 'doc:1', 'extra_attributes' => ['should_return' => 'value1'], 'values' => []],
            ],
        ];

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

        $ftSearchArguments = new SearchArguments();
        $ftSearchArguments->addReturn(1, 'should_return');

        $actualResponse = $redis->ftsearch('idx_hash', '*', $ftSearchArguments);
        $this->assertSame($expectedResponse, $actualResponse);
    }

    public function argumentsProvider(): array
    {
        return [
            'with NOCONTENT modifier' => [
                ['index', '*', (new SearchArguments())->noContent()],
                ['index', '*', 'NOCONTENT'],
            ],
            'with VERBATIM modifier' => [
                ['index', '*', (new SearchArguments())->verbatim()],
                ['index', '*', 'VERBATIM'],
            ],
            'with WITHSCORES modifier' => [
                ['index', '*', (new SearchArguments())->withScores()],
                ['index', '*', 'WITHSCORES'],
            ],
            'with WITHPAYLOADS modifier' => [
                ['index', '*', (new SearchArguments())->withPayloads()],
                ['index', '*', 'WITHPAYLOADS'],
            ],
            'with WITHSORTKEYS modifier' => [
                ['index', '*', (new SearchArguments())->withSortKeys()],
                ['index', '*', 'WITHSORTKEYS'],
            ],
            'with FILTER modifier' => [
                ['index', '*', (new SearchArguments())->searchFilter(['numeric_field', 1, 10])],
                ['index', '*', 'FILTER', 'numeric_field', 1, 10],
            ],
            'with GEOFILTER modifier' => [
                ['index', '*', (new SearchArguments())->geoFilter(['geo_field', 12.213, 14.212, 300, 'km'])],
                ['index', '*', 'GEOFILTER', 'geo_field', 12.213, 14.212, 300, 'km'],
            ],
            'with INKEYS modifier' => [
                ['index', '*', (new SearchArguments())->inKeys(['key1', 'key2'])],
                ['index', '*', 'INKEYS', 2, 'key1', 'key2'],
            ],
            'with INFIELDS modifier' => [
                ['index', '*', (new SearchArguments())->inFields(['field1', 'field2'])],
                ['index', '*', 'INFIELDS', 2, 'field1', 'field2'],
            ],
            'with RETURN modifier' => [
                ['index', '*', (new SearchArguments())->addReturn(2, 'identifier', true, 'property')],
                ['index', '*', 'RETURN', 2, 'identifier', 'AS', 'property'],
            ],
            'with SUMMARIZE modifier' => [
                ['index', '*', (new SearchArguments())->summarize(['field1', 'field2'], 2, 2, ',')],
                ['index', '*', 'SUMMARIZE', 'FIELDS', 2, 'field1', 'field2', 'FRAGS', 2, 'LEN', 2, 'SEPARATOR', ','],
            ],
            'with HIGHLIGHT modifier' => [
                ['index', '*', (new SearchArguments())->highlight(['field1', 'field2'], 'openTag', 'closeTag')],
                ['index', '*', 'HIGHLIGHT', 'FIELDS', 2, 'field1', 'field2', 'TAGS', 'openTag', 'closeTag'],
            ],
            'with SLOP modifier' => [
                ['index', '*', (new SearchArguments())->slop(2)],
                ['index', '*', 'SLOP', 2],
            ],
            'with TIMEOUT modifier' => [
                ['index', '*', (new SearchArguments())->timeout(2)],
                ['index', '*', 'TIMEOUT', 2],
            ],
            'with INORDER modifier' => [
                ['index', '*', (new SearchArguments())->inOrder()],
                ['index', '*', 'INORDER'],
            ],
            'with EXPANDER modifier' => [
                ['index', '*', (new SearchArguments())->expander('expander')],
                ['index', '*', 'EXPANDER', 'expander'],
            ],
            'with SCORER modifier' => [
                ['index', '*', (new SearchArguments())->scorer('scorer')],
                ['index', '*', 'SCORER', 'scorer'],
            ],
            'with EXPLAINSCORE modifier' => [
                ['index', '*', (new SearchArguments())->explainScore()],
                ['index', '*', 'EXPLAINSCORE'],
            ],
            'with PAYLOAD modifier' => [
                ['index', '*', (new SearchArguments())->payload('payload')],
                ['index', '*', 'PAYLOAD', 'payload'],
            ],
            'with SORTBY modifier' => [
                ['index', '*', (new SearchArguments())->sortBy('sort_attribute', 'desc')],
                ['index', '*', 'SORTBY', 'sort_attribute', 'DESC'],
            ],
            'with LIMIT modifier' => [
                ['index', '*', (new SearchArguments())->limit(2, 2)],
                ['index', '*', 'LIMIT', 2, 2],
            ],
            'with PARAMS modifier' => [
                ['index', '*', (new SearchArguments())->params(['name1', 'value2', 'name2', 'value2'])],
                ['index', '*', 'PARAMS', 4, 'name1', 'value2', 'name2', 'value2'],
            ],
            'with DIALECT modifier' => [
                ['index', '*', (new SearchArguments())->dialect('dialect')],
                ['index', '*', 'DIALECT', 'dialect'],
            ],
            'with chain of arguments' => [
                ['index', '*', (new SearchArguments())->withScores()->withPayloads()->searchFilter(['numeric_field', 1, 10])->addReturn(2, 'identifier', true, 'property')],
                ['index', '*', 'WITHSCORES', 'WITHPAYLOADS', 'FILTER', 'numeric_field', 1, 10, 'RETURN', 2, 'identifier', 'AS', 'property'],
            ],
        ];
    }
}
