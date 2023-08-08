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
use Predis\Command\Argument\Search\SchemaFields\TagField;
use Predis\Command\Argument\Search\SchemaFields\TextField;
use Predis\Command\Redis\PredisCommandTestCase;

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
     * @group connected
     * @group relay-incompatible
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
