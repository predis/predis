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

use Predis\Command\Argument\Search\AggregateArguments;
use Predis\Command\Argument\Search\CreateArguments;
use Predis\Command\Argument\Search\CursorArguments;
use Predis\Command\Argument\Search\SchemaFields\AbstractField;
use Predis\Command\Argument\Search\SchemaFields\NumericField;
use Predis\Command\Argument\Search\SchemaFields\TextField;
use Predis\Command\Redis\PredisCommandTestCase;
use Predis\Response\ServerException;

/**
 * @group commands
 * @group realm-stack
 */
class FTCURSOR_Test extends PredisCommandTestCase
{
    /**
     * {@inheritDoc}
     */
    protected function getExpectedCommand(): string
    {
        return FTCURSOR::class;
    }

    /**
     * {@inheritDoc}
     */
    protected function getExpectedId(): string
    {
        return 'FTCURSOR';
    }

    /**
     * @group disconnected
     */
    public function testDelFilterArguments(): void
    {
        $arguments = ['DEL', 'index', 2];
        $expected = ['DEL', 'index', 2];

        $command = $this->getCommand();
        $command->setArguments($arguments);

        $this->assertSameValues($expected, $command->getArguments());
    }

    /**
     * @group disconnected
     * @dataProvider readArgumentsProvider
     */
    public function testReadFilterArguments(array $actualArguments, array $expectedArguments): void
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
    public function testReadAggregatedResultsFromExistingCursor(): void
    {
        $redis = $this->getClient();

        $expectedResponse = [
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
            ->sortBy(0, '@birth', 'DESC')
            ->withCursor(1);

        [$response, $cursor] = $redis->ftaggregate('idx', '@name: "Vlad"', $ftAggregateArguments);
        $actualResponse = [];

        while ($cursor) {
            $actualResponse[] = $response[1];
            [$response, $cursor] = $redis->ftcursor->read('idx', $cursor);
        }

        $this->assertSame($expectedResponse, $actualResponse);
    }

    /**
     * @group connected
     * @return void
     * @requiresRediSearchVersion >= 2.8.0
     */
    public function testReadAggregatedResultsFromExistingCursorResp3(): void
    {
        $redis = $this->getResp3Client();

        $expectedResponse = [
            [
                'attributes' => [],
                'error' => [],
                'total_results' => 2,
                'format' => 'STRING',
                'results' => [
                    [
                        'extra_attributes' => ['country' => 'Ukraine', 'birth' => '1995', 'country_birth_Vlad_count' => '2'],
                        'values' => [],
                    ],
                ],
            ],
            [
                'attributes' => [],
                'error' => [],
                'total_results' => 0,
                'format' => 'STRING',
                'results' => [
                    [
                        'extra_attributes' => ['country' => 'Israel', 'birth' => '1994', 'country_birth_Vlad_count' => '1'],
                        'values' => [],
                    ],
                ],
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
            ->sortBy(0, '@birth', 'DESC')
            ->withCursor(1);

        [$response, $cursor] = $redis->ftaggregate('idx', '@name: "Vlad"', $ftAggregateArguments);
        $actualResponse = [];

        while ($cursor) {
            $actualResponse[] = $response;
            [$response, $cursor] = $redis->ftcursor->read('idx', $cursor);
        }

        $this->assertSame($expectedResponse, $actualResponse);
    }

    /**
     * @group connected
     * @group relay-resp3
     * @return void
     * @requiresRediSearchVersion >= 1.1.0
     */
    public function testDelExplicitlyRemovesExistingCursor(): void
    {
        $redis = $this->getClient();

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
            ->sortBy(0, '@birth', 'DESC')
            ->withCursor(1);

        [$_, $cursor] = $redis->ftaggregate('idx', '@name: "Vlad"', $ftAggregateArguments);

        $this->assertEquals('OK', $redis->ftcursor->del('idx', $cursor));
    }

    /**
     * @group connected
     * @group relay-resp3
     * @return void
     * @requiresRediSearchVersion >= 1.1.0
     */
    public function testReadThrowsExceptionOnWrongCursorId(): void
    {
        $redis = $this->getClient();

        $this->expectException(ServerException::class);
        $this->expectExceptionMessage('Cursor not found');

        $redis->ftcursor->read('idx', 21412412);
    }

    /**
     * @group connected
     * @group relay-resp3
     * @return void
     * @requiresRediSearchVersion >= 1.1.0
     */
    public function testDelThrowsExceptionOnWrongCursorId(): void
    {
        $redis = $this->getClient();

        $this->expectException(ServerException::class);
        $this->expectExceptionMessage('Cursor does not exist');

        $redis->ftcursor->del('idx', 21412412);
    }

    public function readArgumentsProvider(): array
    {
        return [
            'with default arguments' => [
                ['READ', 'index', 2],
                ['READ', 'index', 2],
            ],
            'with COUNT modifier' => [
                ['READ', 'index', 2, (new CursorArguments())->count(2)],
                ['READ', 'index', 2, 'COUNT', 2],
            ],
        ];
    }
}
