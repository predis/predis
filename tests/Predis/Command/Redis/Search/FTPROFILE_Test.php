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

use Predis\Command\Argument\Search\Schema;
use Predis\Command\Argument\Search\SearchArguments;
use Predis\Command\Redis\PredisCommandTestCase;
use Predis\Response\ServerException;

class FTPROFILE_Test extends PredisCommandTestCase
{
    /**
     * {@inheritDoc}
     */
    protected function getExpectedCommand(): string
    {
        return FTPROFILE::class;
    }

    /**
     * {@inheritDoc}
     */
    protected function getExpectedId(): string
    {
        return 'FTPROFILE';
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
     * @dataProvider queryProvider
     * @param  array $createArguments
     * @param  array $profileArguments
     * @return void
     * @requiresRediSearchVersion >= 2.2.0
     */
    public function testProfileReturnsPerformanceInformationAboutGivenQuery(
        array $createArguments,
        array $profileArguments
    ): void {
        $redis = $this->getClient();

        $this->assertEquals('OK', $redis->ftcreate(...$createArguments));
        $this->assertNotEmpty($redis->ftprofile(...$profileArguments));
    }

    /**
     * @group connected
     * @return void
     * @requiresRediSearchVersion >= 2.2.0
     */
    public function testThrowsExceptionOnNonExistingIndex(): void
    {
        $redis = $this->getClient();

        $this->expectException(ServerException::class);
        $this->expectExceptionMessage('index: no such index');

        $redis->ftprofile('index', (new SearchArguments())->search()->query('query'));
    }

    public function argumentsProvider(): array
    {
        return [
            'with default arguments - SEARCH' => [
                ['index', (new SearchArguments())->search()->query('query')],
                ['index', 'SEARCH', 'QUERY', 'query'],
            ],
            'with default arguments - AGGREGATE' => [
                ['index', (new SearchArguments())->aggregate()->query('query')],
                ['index', 'AGGREGATE', 'QUERY', 'query'],
            ],
            'with LIMITED modifier' => [
                ['index', (new SearchArguments())->aggregate()->limited()->query('query')],
                ['index', 'AGGREGATE', 'LIMITED', 'QUERY', 'query'],
            ],
        ];
    }

    public function queryProvider(): array
    {
        return [
            'with SEARCH context' => [
                ['index', (new Schema())->addTextField('text_field')],
                ['index', (new SearchArguments())->search()->query('hello world')],
            ],
            'with AGGREGATE context' => [
                ['index', (new Schema())->addTextField('text_field')],
                ['index', (new SearchArguments())->aggregate()->query('hello world')],
            ],
        ];
    }
}
