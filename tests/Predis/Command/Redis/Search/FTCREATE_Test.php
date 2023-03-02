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
use Predis\Command\Argument\Search\Schema;
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

        $schema = new Schema();
        $schema->addTextField('first', 'fst', true, true);
        $schema->addTextField('last');
        $schema->addNumericField('age');

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
                ['index', (new Schema())->addTextField('field_name')],
                ['index', 'SCHEMA', 'field_name', 'TEXT'],
            ],
            'with ON modifier - HASH' => [
                ['index', (new Schema())->addTextField('field_name'), 'hash'],
                ['index', 'ON', 'HASH', 'SCHEMA', 'field_name', 'TEXT'],
            ],
            'with ON modifier - JSON' => [
                ['index', (new Schema())->addTextField('field_name'), 'json'],
                ['index', 'ON', 'JSON', 'SCHEMA', 'field_name', 'TEXT'],
            ],
            'with prefixes' => [
                ['index', (new Schema())->addTextField('field_name'), '', ['prefix1:', 'prefix2:']],
                ['index', 'PREFIX', 2, 'prefix1:', 'prefix2:', 'SCHEMA', 'field_name', 'TEXT'],
            ],
            'with FILTER' => [
                ['index', (new Schema())->addTextField('field_name'), '', [], '@age>16'],
                ['index', 'FILTER', '@age>16', 'SCHEMA', 'field_name', 'TEXT'],
            ],
            'with LANGUAGE' => [
                ['index', (new Schema())->addTextField('field_name'), '', [], '', 'english'],
                ['index', 'LANGUAGE', 'english', 'SCHEMA', 'field_name', 'TEXT'],
            ],
            'with LANGUAGE_FIELD' => [
                ['index', (new Schema())->addTextField('field_name'), '', [], '', '', 'language_attribute'],
                ['index', 'LANGUAGE_FIELD', 'language_attribute', 'SCHEMA', 'field_name', 'TEXT'],
            ],
            'with SCORE' => [
                ['index', (new Schema())->addTextField('field_name'), '', [], '', '', '', 1.0],
                ['index', 'SCORE', 1.0, 'SCHEMA', 'field_name', 'TEXT'],
            ],
            'with SCORE_FIELD' => [
                ['index', (new Schema())->addTextField('field_name'), '', [], '', '', '', 0, 'score_attribute'],
                ['index', 'SCORE_FIELD', 'score_attribute', 'SCHEMA', 'field_name', 'TEXT'],
            ],
            'with PAYLOAD_FIELD' => [
                ['index', (new Schema())->addTextField('field_name'), '', [], '', '', '', 0, '', 'payload_attribute'],
                ['index', 'PAYLOAD_FIELD', 'payload_attribute', 'SCHEMA', 'field_name', 'TEXT'],
            ],
            'with MAXTEXTFIELDS' => [
                ['index', (new Schema())->addTextField('field_name'), '', [], '', '', '', 0, '', '', true],
                ['index', 'MAXTEXTFIELDS', 'SCHEMA', 'field_name', 'TEXT'],
            ],
            'with TEMPORARY' => [
                ['index', (new Schema())->addTextField('field_name'), '', [], '', '', '', 0, '', '', false, 1],
                ['index', 'TEMPORARY', 1, 'SCHEMA', 'field_name', 'TEXT'],
            ],
            'with NOOFFSETS' => [
                ['index', (new Schema())->addTextField('field_name'), '', [], '', '', '', 0, '', '', false, 0, true],
                ['index', 'NOOFFSETS', 'SCHEMA', 'field_name', 'TEXT'],
            ],
            'with NOHL' => [
                ['index', (new Schema())->addTextField('field_name'), '', [], '', '', '', 0, '', '', false, 0, false, true],
                ['index', 'NOHL', 'SCHEMA', 'field_name', 'TEXT'],
            ],
            'with NOFIELDS' => [
                ['index', (new Schema())->addTextField('field_name'), '', [], '', '', '', 0, '', '', false, 0, false, false, true],
                ['index', 'NOFIELDS', 'SCHEMA', 'field_name', 'TEXT'],
            ],
            'with NOFREQS' => [
                ['index', (new Schema())->addTextField('field_name'), '', [], '', '', '', 0, '', '', false, 0, false, false, false, true],
                ['index', 'NOFREQS', 'SCHEMA', 'field_name', 'TEXT'],
            ],
            'with STOPWORDS' => [
                ['index', (new Schema())->addTextField('field_name'), '', [], '', '', '', 0, '', '', false, 0, false, false, false, false, ['word1', 'word2']],
                ['index', 'STOPWORDS', 2, 'word1', 'word2', 'SCHEMA', 'field_name', 'TEXT'],
            ],
            'with SKIPINITIALSCAN' => [
                ['index', (new Schema())->addTextField('field_name'), '', [], '', '', '', 0, '', '', false, 0, false, false, false, false, [], true],
                ['index', 'SKIPINITIALSCAN', 'SCHEMA', 'field_name', 'TEXT'],
            ],
            'with chain of arguments' => [
                ['index', (new Schema())->addTextField('field_name'), 'hash', ['prefix1:', 'prefix2:'], '@age>16'],
                ['index', 'ON', 'HASH', 'PREFIX', 2, 'prefix1:', 'prefix2:', 'FILTER', '@age>16', 'SCHEMA', 'field_name', 'TEXT'],
            ],
            'with multiple fields schema' => [
                ['index', (new Schema())->addTextField('field_name')->addNumericField('numeric_field')->addTagField('tag_field', 'tf'), 'hash'],
                ['index', 'ON', 'HASH', 'SCHEMA', 'field_name', 'TEXT', 'numeric_field', 'NUMERIC', 'tag_field', 'AS', 'tf', 'TAG'],
            ],
        ];
    }
}
