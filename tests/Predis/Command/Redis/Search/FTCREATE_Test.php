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

        $actualResponse = $redis->ftcreate(
            'index',
            $schema,
            '',
            ['prefix:', 'prefix1:'],
            '@age>16',
            '',
            '',
            0,
            '',
            false,
            0,
            false,
            false,
            false,
            false,
            ['hello', 'world']
        );

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
                ['index', [new TextField('field_name')], 'hash'],
                ['index', 'ON', 'HASH', 'SCHEMA', 'field_name', 'TEXT'],
            ],
            'with ON modifier - JSON' => [
                ['index', [new TextField('field_name')], 'json'],
                ['index', 'ON', 'JSON', 'SCHEMA', 'field_name', 'TEXT'],
            ],
            'with prefixes' => [
                ['index', [new TextField('field_name')], 'hash', ['prefix1:', 'prefix2:']],
                ['index', 'ON', 'HASH', 'PREFIX', 2, 'prefix1:', 'prefix2:', 'SCHEMA', 'field_name', 'TEXT'],
            ],
            'with FILTER' => [
                ['index', [new TextField('field_name')], 'hash', [], '@age>16'],
                ['index', 'ON', 'HASH', 'FILTER', '@age>16', 'SCHEMA', 'field_name', 'TEXT'],
            ],
            'with LANGUAGE' => [
                ['index', [new TextField('field_name')], 'hash', [], '', 'english'],
                ['index', 'ON', 'HASH', 'LANGUAGE', 'english', 'SCHEMA', 'field_name', 'TEXT'],
            ],
            'with LANGUAGE_FIELD' => [
                ['index', [new TextField('field_name')], 'hash', [], '', 'english', 'language_attribute'],
                ['index', 'ON', 'HASH', 'LANGUAGE', 'english', 'LANGUAGE_FIELD', 'language_attribute', 'SCHEMA', 'field_name', 'TEXT'],
            ],
            'with SCORE' => [
                ['index', [new TextField('field_name')], 'hash', [], '', 'english', '', 1.0],
                ['index', 'ON', 'HASH', 'LANGUAGE', 'english', 'SCORE', 1.0, 'SCHEMA', 'field_name', 'TEXT'],
            ],
            'with SCORE_FIELD' => [
                ['index', [new TextField('field_name')], 'hash', [], '', 'english', '', 1.0, 'score_attribute'],
                ['index', 'ON', 'HASH', 'LANGUAGE', 'english', 'SCORE', 1.0, 'SCORE_FIELD', 'score_attribute', 'SCHEMA', 'field_name', 'TEXT'],
            ],
            'with MAXTEXTFIELDS' => [
                ['index', [new TextField('field_name')], 'hash', [], '', 'english', '', 1.0, '', true],
                ['index', 'ON', 'HASH', 'LANGUAGE', 'english', 'SCORE', 1.0, 'MAXTEXTFIELDS', 'SCHEMA', 'field_name', 'TEXT'],
            ],
            'with TEMPORARY' => [
                ['index', [new TextField('field_name')], 'hash', [], '', 'english', '', 1.0, '', false, 1],
                ['index', 'ON', 'HASH', 'LANGUAGE', 'english', 'SCORE', 1.0, 'TEMPORARY', 1, 'SCHEMA', 'field_name', 'TEXT'],
            ],
            'with NOOFFSETS' => [
                ['index', [new TextField('field_name')], 'hash', [], '', 'english', '', 1.0, '', false, 0, true],
                ['index', 'ON', 'HASH', 'LANGUAGE', 'english', 'SCORE', 1.0, 'NOOFFSETS', 'SCHEMA', 'field_name', 'TEXT'],
            ],
            'with NOHL' => [
                ['index', [new TextField('field_name')], 'hash', [], '', 'english', '', 1.0, '', false, 0, false, true],
                ['index', 'ON', 'HASH', 'LANGUAGE', 'english', 'SCORE', 1.0, 'NOHL', 'SCHEMA', 'field_name', 'TEXT'],
            ],
            'with NOFIELDS' => [
                ['index', [new TextField('field_name')], 'hash', [], '', 'english', '', 1.0, '', false, 0, false, false, true],
                ['index', 'ON', 'HASH', 'LANGUAGE', 'english', 'SCORE', 1.0, 'NOFIELDS', 'SCHEMA', 'field_name', 'TEXT'],
            ],
            'with NOFREQS' => [
                ['index', [new TextField('field_name')], 'hash', [], '', 'english', '', 1.0, '', false, 0, false, false, false, true],
                ['index', 'ON', 'HASH', 'LANGUAGE', 'english', 'SCORE', 1.0, 'NOFREQS', 'SCHEMA', 'field_name', 'TEXT'],
            ],
            'with STOPWORDS' => [
                ['index', [new TextField('field_name')], 'hash', [], '', 'english', '', 1.0, '', false, 0, false, false, false, false, ['word1', 'word2']],
                ['index', 'ON', 'HASH', 'LANGUAGE', 'english', 'SCORE', 1.0, 'STOPWORDS', 2, 'word1', 'word2', 'SCHEMA', 'field_name', 'TEXT'],
            ],
            'with SKIPINITIALSCAN' => [
                ['index', [new TextField('field_name')], 'hash', [], '', 'english', '', 1.0, '', false, 0, false, false, false, false, [], true],
                ['index', 'ON', 'HASH', 'LANGUAGE', 'english', 'SCORE', 1.0, 'SKIPINITIALSCAN', 'SCHEMA', 'field_name', 'TEXT'],
            ],
            'with chain of arguments' => [
                ['index', [new TextField('field_name')], 'hash', ['prefix1:', 'prefix2:'], '@age>16'],
                ['index', 'ON', 'HASH', 'PREFIX', 2, 'prefix1:', 'prefix2:', 'FILTER', '@age>16', 'SCHEMA', 'field_name', 'TEXT'],
            ],
            'with multiple fields schema' => [
                ['index', [new TextField('text_field'), new NumericField('numeric_field'), new TagField('tag_field', 'tf')], 'hash'],
                ['index', 'ON', 'HASH', 'SCHEMA', 'text_field', 'TEXT', 'numeric_field', 'NUMERIC', 'tag_field', 'AS', 'tf', 'TAG'],
            ],
        ];
    }
}
