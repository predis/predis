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
use Predis\Command\Argument\Search\SchemaFields\GeoField;
use Predis\Command\Argument\Search\SchemaFields\NumericField;
use Predis\Command\Argument\Search\SchemaFields\TagField;
use Predis\Command\Argument\Search\SchemaFields\TextField;
use Predis\Command\Argument\Search\SchemaFields\VectorField;
use Predis\Command\Argument\Search\SearchArguments;
use Predis\Command\PrefixableCommand;
use Predis\Command\Redis\PredisCommandTestCase;
use Predis\Command\Redis\Utils\VectorUtility;

/**
 * @group commands
 * @group realm-stack
 */
class FTCREATE_Test extends PredisCommandTestCase
{
    /**
     * {@inheritDoc}
     */
    protected function getExpectedCommand(): string
    {
        return FTCREATE::class;
    }

    /**
     * {@inheritDoc}
     */
    protected function getExpectedId(): string
    {
        return 'FTCREATE';
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
    public function testParseResponse(): void
    {
        $this->assertSame(1, $this->getCommand()->parseResponse(1));
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
     */
    public function testCreatesSearchIndexWithGivenArgumentsAndSchema(): void
    {
        $redis = $this->getClient();

        $schema = [
            new TextField('first', 'fst', true, true),
            new TextField('last'),
            new NumericField('age'),
        ];

        $arguments = new CreateArguments();
        $arguments->prefix(['prefix:', 'prefix1:']);
        $arguments->filter('@age>16');
        $arguments->stopWords(['hello', 'world']);

        $actualResponse = $redis->ftcreate('index', $schema, $arguments);

        $this->assertEquals('OK', $actualResponse);
    }

    /**
     * @group connected
     * @return void
     * @requiresRediSearchVersion >= 2.8.0
     */
    public function testCreatesSearchIndexWithGivenArgumentsAndSchemaResp3(): void
    {
        $redis = $this->getResp3Client();

        $schema = [
            new TextField('first', 'fst', true, true),
            new TextField('last'),
            new NumericField('age'),
        ];

        $arguments = new CreateArguments();
        $arguments->prefix(['prefix:', 'prefix1:']);
        $arguments->filter('@age>16');
        $arguments->stopWords(['hello', 'world']);

        $actualResponse = $redis->ftcreate('index', $schema, $arguments);

        $this->assertEquals('OK', $actualResponse);
    }

    /**
     * @group connected
     * @group relay-resp3
     * @return void
     * @requiresRediSearchVersion >= 2.09.00
     */
    public function testCreatesSearchIndexWithFloat16Vector(): void
    {
        $redis = $this->getClient();

        $schema = [
            new VectorField('float16',
                'FLAT',
                ['TYPE', 'FLOAT16', 'DIM', 768, 'DISTANCE_METRIC', 'COSINE']
            ),
            new VectorField('bfloat16',
                'FLAT',
                ['TYPE', 'BFLOAT16', 'DIM', 768, 'DISTANCE_METRIC', 'COSINE']
            ),
        ];

        $actualResponse = $redis->ftcreate('index', $schema);

        $this->assertEquals('OK', $actualResponse);
    }

    /**
     * @group connected
     * @group relay-resp3
     * @return void
     * @requiresRediSearchVersion >= 2.09.00
     */
    public function testCreatesSearchIndexWithMissingAndEmptyFields(): void
    {
        $redis = $this->getClient();

        $schema = [
            new TextField(
                'text_empty',
                '',
                false, false, false, '', 1, false, true
            ),
            new TagField('tag_empty',
                '', false, false, ',', false, true
            ),
            new NumericField('num_missing', '', false, false, true),
            new GeoField('geo_missing', '', false, false, true),
            new TextField(
                'text_empty_missing',
                '',
                false,
                false, false, '', 1, false, true, true
            ),
            new TagField('tag_empty_missing',
                '', false, false, ',', false, true, true
            ),
        ];

        $actualResponse = $redis->ftcreate('index', $schema);

        $this->assertEquals('OK', $actualResponse);
    }

    /**
     * @group connected
     * @group relay-resp3
     * @requiresRedisVersion >= 8.1.0
     * @return void
     */
    public function testVectorCreateVANAMA(): void
    {
        $redis = $this->getClient();

        $this->assertEquals('OK', $redis->ftcreate('test', [
            new VectorField(
                'v', 'SVS-VAMANA',
                ['TYPE', 'FLOAT32',
                    'DIM', 8,
                    'DISTANCE_METRIC', 'L2',
                    'COMPRESSION', 'LeanVec8x8',  // LeanVec compression required for REDUCE
                    'CONSTRUCTION_WINDOW_SIZE', 200,
                    'GRAPH_MAX_DEGREE', 32,
                    'SEARCH_WINDOW_SIZE', 15,
                    'EPSILON', 0.01,
                    'TRAINING_THRESHOLD', 1024,
                    'REDUCE', 4,
                ]// Half of DIM (8/2 = 4)
            ),
        ]));

        $this->sleep(0.1);

        // Create test vectors (8-dimensional to match DIM)
        $vectors = [
            [1.0, 2.0, 3.0, 4.0, 5.0, 6.0, 7.0, 8.0],
            [2.0, 3.0, 4.0, 5.0, 6.0, 7.0, 8.0, 9.0],
            [3.0, 4.0, 5.0, 6.0, 7.0, 8.0, 9.0, 10.0],
            [4.0, 5.0, 6.0, 7.0, 8.0, 9.0, 10.0, 11.0],
            [5.0, 6.0, 7.0, 8.0, 9.0, 10.0, 11.0, 12.0],
        ];

        foreach ($vectors as $i => $vector) {
            $redis->hset("doc{$i}", 'v', VectorUtility::toBlob($vector));
        }

        $query = new SearchArguments();
        $query->params(['vec', VectorUtility::toBlob($vectors[0])]);
        $query->noContent();

        $result = $redis->ftsearch('test', '*=>[KNN 3 @v $vec as score]', $query);

        $this->assertSame(3, $result[0]);
        $this->assertSame('doc0', $result[1]);
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 8.1.0
     * @return void
     */
    public function testVectorCreateVANAMAResp3(): void
    {
        $redis = $this->getResp3Client();

        $this->assertEquals('OK', $redis->ftcreate('test', [
            new VectorField(
                'v', 'SVS-VAMANA',
                ['TYPE', 'FLOAT32',
                    'DIM', 8,
                    'DISTANCE_METRIC', 'L2',
                    'COMPRESSION', 'LeanVec8x8',  // LeanVec compression required for REDUCE
                    'CONSTRUCTION_WINDOW_SIZE', 200,
                    'GRAPH_MAX_DEGREE', 32,
                    'SEARCH_WINDOW_SIZE', 15,
                    'EPSILON', 0.01,
                    'TRAINING_THRESHOLD', 1024,
                    'REDUCE', 4,
                ]// Half of DIM (8/2 = 4)
            ),
        ]));

        $this->sleep(0.1);

        // Create test vectors (8-dimensional to match DIM)
        $vectors = [
            [1.0, 2.0, 3.0, 4.0, 5.0, 6.0, 7.0, 8.0],
            [2.0, 3.0, 4.0, 5.0, 6.0, 7.0, 8.0, 9.0],
            [3.0, 4.0, 5.0, 6.0, 7.0, 8.0, 9.0, 10.0],
            [4.0, 5.0, 6.0, 7.0, 8.0, 9.0, 10.0, 11.0],
            [5.0, 6.0, 7.0, 8.0, 9.0, 10.0, 11.0, 12.0],
        ];

        foreach ($vectors as $i => $vector) {
            $redis->hset("doc{$i}", 'v', VectorUtility::toBlob($vector));
        }

        $query = new SearchArguments();
        $query->params(['vec', VectorUtility::toBlob($vectors[0])]);
        $query->noContent();

        $result = $redis->ftsearch('test', '*=>[KNN 3 @v $vec as score]', $query);

        $this->assertSame(3, count($result['results']));
        $this->assertSame('doc0', $result['results'][0]['id']);
    }

    public function argumentsProvider(): array
    {
        return [
            'without arguments' => [
                ['index', [new TextField('field_name')]],
                ['index', 'SCHEMA', 'field_name', 'TEXT'],
            ],
            'with ON modifier - HASH' => [
                ['index', [new TextField('field_name')], (new CreateArguments())->on()],
                ['index', 'ON', 'HASH', 'SCHEMA', 'field_name', 'TEXT'],
            ],
            'with ON modifier - JSON' => [
                ['index', [new TextField('field_name')], (new CreateArguments())->on('JSON')],
                ['index', 'ON', 'JSON', 'SCHEMA', 'field_name', 'TEXT'],
            ],
            'with prefixes' => [
                ['index', [new TextField('field_name')], (new CreateArguments())->prefix(['prefix1:', 'prefix2:'])],
                ['index', 'PREFIX', 2, 'prefix1:', 'prefix2:', 'SCHEMA', 'field_name', 'TEXT'],
            ],
            'with FILTER' => [
                ['index', [new TextField('field_name')], (new CreateArguments())->filter('@age>16')],
                ['index', 'FILTER', '@age>16', 'SCHEMA', 'field_name', 'TEXT'],
            ],
            'with LANGUAGE' => [
                ['index', [new TextField('field_name')], (new CreateArguments())->language()],
                ['index', 'LANGUAGE', 'english', 'SCHEMA', 'field_name', 'TEXT'],
            ],
            'with LANGUAGE_FIELD' => [
                ['index', [new TextField('field_name')], (new CreateArguments())->languageField('language_attribute')],
                ['index', 'LANGUAGE_FIELD', 'language_attribute', 'SCHEMA', 'field_name', 'TEXT'],
            ],
            'with SCORE' => [
                ['index', [new TextField('field_name')], (new CreateArguments())->score()],
                ['index', 'SCORE', 1.0, 'SCHEMA', 'field_name', 'TEXT'],
            ],
            'with SCORE_FIELD' => [
                ['index', [new TextField('field_name')], (new CreateArguments())->scoreField('score_attribute')],
                ['index', 'SCORE_FIELD', 'score_attribute', 'SCHEMA', 'field_name', 'TEXT'],
            ],
            'with MAXTEXTFIELDS' => [
                ['index', [new TextField('field_name')], (new CreateArguments())->maxTextFields()],
                ['index', 'MAXTEXTFIELDS', 'SCHEMA', 'field_name', 'TEXT'],
            ],
            'with TEMPORARY' => [
                ['index', [new TextField('field_name')], (new CreateArguments())->temporary(1)],
                ['index', 'TEMPORARY', 1, 'SCHEMA', 'field_name', 'TEXT'],
            ],
            'with NOOFFSETS' => [
                ['index', [new TextField('field_name')], (new CreateArguments())->noOffsets()],
                ['index', 'NOOFFSETS', 'SCHEMA', 'field_name', 'TEXT'],
            ],
            'with NOHL' => [
                ['index', [new TextField('field_name')], (new CreateArguments())->noHl()],
                ['index', 'NOHL', 'SCHEMA', 'field_name', 'TEXT'],
            ],
            'with NOFIELDS' => [
                ['index', [new TextField('field_name')], (new CreateArguments())->noFields()],
                ['index', 'NOFIELDS', 'SCHEMA', 'field_name', 'TEXT'],
            ],
            'with NOFREQS' => [
                ['index', [new TextField('field_name')], (new CreateArguments())->noFreqs()],
                ['index', 'NOFREQS', 'SCHEMA', 'field_name', 'TEXT'],
            ],
            'with STOPWORDS' => [
                ['index', [new TextField('field_name')], (new CreateArguments())->stopWords(['word1', 'word2'])],
                ['index', 'STOPWORDS', 2, 'word1', 'word2', 'SCHEMA', 'field_name', 'TEXT'],
            ],
            'with SKIPINITIALSCAN' => [
                ['index', [new TextField('field_name')], (new CreateArguments())->skipInitialScan()],
                ['index', 'SKIPINITIALSCAN', 'SCHEMA', 'field_name', 'TEXT'],
            ],
            'with chain of arguments' => [
                ['index', [new TextField('field_name')], (new CreateArguments())->on()->prefix(['prefix1:', 'prefix2:'])->filter('@age>16')],
                ['index', 'ON', 'HASH', 'PREFIX', 2, 'prefix1:', 'prefix2:', 'FILTER', '@age>16', 'SCHEMA', 'field_name', 'TEXT'],
            ],
            'with multiple fields schema' => [
                ['index', [new TextField('text_field'), new NumericField('numeric_field'), new TagField('tag_field', 'tf')], (new CreateArguments())->on()],
                ['index', 'ON', 'HASH', 'SCHEMA', 'text_field', 'TEXT', 'numeric_field', 'NUMERIC', 'tag_field', 'AS', 'tf', 'TAG'],
            ],
        ];
    }
}
