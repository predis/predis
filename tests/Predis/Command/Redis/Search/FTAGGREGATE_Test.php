<?php

/*
 * This file is part of the Predis package.
 *
 * (c) 2009-2020 Daniele Alessandri
 * (c) 2021-2024 Till Krüss
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Command\Redis\Search;

use Predis\Command\Argument\Search\AggregateArguments;
use Predis\Command\Argument\Search\CreateArguments;
use Predis\Command\Argument\Search\SchemaFields\AbstractField;
use Predis\Command\Argument\Search\SchemaFields\NumericField;
use Predis\Command\Argument\Search\SchemaFields\TextField;
use Predis\Command\Redis\PredisCommandTestCase;
use Predis\Response\ServerException;

/**
 * @group commands
 * @group realm-stack
 */
class FTAGGREGATE_Test extends PredisCommandTestCase
{
    /**
     * {@inheritDoc}
     */
    protected function getExpectedCommand(): string
    {
        return FTAGGREGATE::class;
    }

    /**
     * {@inheritDoc}
     */
    protected function getExpectedId(): string
    {
        return 'FTAGGREGATE';
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
     * @group relay-resp3
     * @return void
     * @requiresRediSearchVersion >= 1.1.0
     */
    public function testReturnsAggregatedSearchResultWithGivenModifiers(): void
    {
        $redis = $this->getClient();
        $expectedResponse = [
            2,
            [
                'country', 'Ukraine', 'birth', '1995', 'country_birth_Vlad_count', '2',
            ],
            [
                'country', 'Israel', 'birth', '1994', 'country_birth_Vlad_count', '1',
            ],
        ];

        $ftCreateArguments = (new CreateArguments())->prefix(['user:']);
        $schema = [
            new TextField('name'),
            new TextField('country'),
            new NumericField('dob', '', AbstractField::SORTABLE),
        ];

        $this->assertEquals('OK', $redis->ftcreate('idx', $schema, $ftCreateArguments));
        $this->assertSame(
            3,
            $redis->hset('user:0', 'name', 'Vlad', 'country', 'Ukraine', 'dob', 813801600)
        );
        $this->assertSame(
            3,
            $redis->hset('user:1', 'name', 'Vlad', 'country', 'Israel', 'dob', 782265600)
        );
        $this->assertSame(
            3,
            $redis->hset('user:2', 'name', 'Vlad', 'country', 'Ukraine', 'dob', 813801600)
        );

        $ftAggregateArguments = (new AggregateArguments())
            ->apply('year(@dob)', 'birth')
            ->groupBy('@country', '@birth')
            ->reduce('COUNT', true, 'country_birth_Vlad_count')
            ->sortBy(0, '@birth', 'DESC');

        $this->assertSame(
            $expectedResponse,
            $redis->ftaggregate('idx', '@name: "Vlad"', $ftAggregateArguments)
        );
    }

    /**
     * @group connected
     * @return void
     * @requiresRediSearchVersion >= 1.1.0
     */
    public function testThrowsExceptionOnNonExistingIndex(): void
    {
        $redis = $this->getClient();

        $this->expectException(ServerException::class);
        $this->expectExceptionMessage('index: no such index');

        $redis->ftaggregate('index', 'query');
    }

    public function argumentsProvider(): array
    {
        return [
            'with default arguments' => [
                ['index', 'query'],
                ['index', 'query'],
            ],
            'with VERBATIM modifier' => [
                ['index', 'query', (new AggregateArguments())->verbatim()],
                ['index', 'query', 'VERBATIM'],
            ],
            'with LOAD modifier - specified fields' => [
                ['index', 'query', (new AggregateArguments())->load('field1', 'field2')],
                ['index', 'query', 'LOAD', 2, 'field1', 'field2'],
            ],
            'with LOAD modifier - all fields' => [
                ['index', 'query', (new AggregateArguments())->load('*')],
                ['index', 'query', 'LOAD', '*'],
            ],
            'with TIMEOUT modifier' => [
                ['index', 'query', (new AggregateArguments())->timeout(2)],
                ['index', 'query', 'TIMEOUT', 2],
            ],
            'with GROUPBY modifier' => [
                ['index', 'query', (new AggregateArguments())->groupBy('property1', 'property2')],
                ['index', 'query', 'GROUPBY', 2, 'property1', 'property2'],
            ],
            'with REDUCE modifier' => [
                ['index', 'query', (new AggregateArguments())->reduce('function', 'arg1', true, 'alias1', 'arg2')],
                ['index', 'query', 'REDUCE', 'function', 2, 'arg1', 'AS', 'alias1', 'arg2'],
            ],
            'with SORTBY modifier' => [
                ['index', 'query', (new AggregateArguments())->sortBy(2, 'property1', 'ASC', 'property2', 'DESC')],
                ['index', 'query', 'SORTBY', 2, 'property1', 'ASC', 'property2', 'DESC', 'MAX', 2],
            ],
            'with APPLY modifier' => [
                ['index', 'query', (new AggregateArguments())->apply('expression', 'name')],
                ['index', 'query', 'APPLY', 'expression', 'AS', 'name'],
            ],
            'with LIMIT modifier' => [
                ['index', 'query', (new AggregateArguments())->limit(2, 3)],
                ['index', 'query', 'LIMIT', 2, 3],
            ],
            'with FILTER modifier' => [
                ['index', 'query', (new AggregateArguments())->filter('filter')],
                ['index', 'query', 'FILTER', 'filter'],
            ],
            'with WITHCURSOR modifier' => [
                ['index', 'query', (new AggregateArguments())->withCursor(10, 20)],
                ['index', 'query', 'WITHCURSOR', 'COUNT', 10, 'MAXIDLE', 20],
            ],
            'with PARAMS modifier' => [
                ['index', 'query', (new AggregateArguments())->params(['name1', 'value1', 'name2', 'value2'])],
                ['index', 'query', 'PARAMS', 4, 'name1', 'value1', 'name2', 'value2'],
            ],
            'with DIALECT modifier' => [
                ['index', 'query', (new AggregateArguments())->dialect('dialect')],
                ['index', 'query', 'DIALECT', 'dialect'],
            ],
            'with chain of arguments' => [
                [
                    'index',
                    '@name: "test"',
                    (new AggregateArguments())
                        ->apply('year(@dob)', 'birth')
                        ->groupBy('@birth', '@country')
                        ->reduce('COUNT', true, 'num_visits')
                        ->sortBy(0, '@day'),
                ],
                [
                    'index', '@name: "test"', 'APPLY', 'year(@dob)', 'AS', 'birth', 'GROUPBY', 2, '@birth', '@country',
                    'REDUCE', 'COUNT', 0, 'AS', 'num_visits', 'SORTBY', 1, '@day',
                ],
            ],
        ];
    }
}
