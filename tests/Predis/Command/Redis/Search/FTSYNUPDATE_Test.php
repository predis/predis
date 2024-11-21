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

use Predis\Command\Argument\Search\SchemaFields\TextField;
use Predis\Command\Argument\Search\SynUpdateArguments;
use Predis\Command\Redis\PredisCommandTestCase;
use Predis\Response\ServerException;

/**
 * @group commands
 * @group realm-stack
 */
class FTSYNUPDATE_Test extends PredisCommandTestCase
{
    /**
     * {@inheritDoc}
     */
    protected function getExpectedCommand(): string
    {
        return FTSYNUPDATE::class;
    }

    /**
     * {@inheritDoc}
     */
    protected function getExpectedId(): string
    {
        return 'FTSYNUPDATE';
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
     * @requiresRediSearchVersion >= 1.2.0
     */
    public function testCreatesSynonymGroupWithinGivenIndex(): void
    {
        $redis = $this->getClient();

        $this->assertEquals(
            'OK',
            $redis->ftcreate('index', [new TextField('text')])
        );

        $this->assertEquals(
            'OK',
            $redis->ftsynupdate('index', 'synonym1', null, 'term1', 'term2')
        );
    }

    /**
     * @group connected
     * @return void
     * @requiresRediSearchVersion >= 1.2.0
     */
    public function testUpdatesAlreadyExistingSynonymGroupWithinGivenIndex(): void
    {
        $redis = $this->getClient();

        $this->assertEquals(
            'OK',
            $redis->ftcreate('index', [new TextField('text')])
        );

        $this->assertEquals(
            'OK',
            $redis->ftsynupdate('index', 'synonym1', null, 'term1', 'term2')
        );

        $this->assertEquals(
            'OK',
            $redis->ftsynupdate('index', 'synonym1', null, 'term3', 'term4')
        );
    }

    /**
     * @group connected
     * @group relay-resp3
     * @return void
     * @requiresRediSearchVersion >= 1.2.0
     */
    public function testThrowsExceptionOnNonExistingIndex(): void
    {
        $redis = $this->getClient();

        $this->expectException(ServerException::class);
        $this->expectExceptionMessage('Unknown index name');

        $redis->ftsynupdate(
            'index',
            'synonym1',
            null,
            'term1'
        );
    }

    public function argumentsProvider(): array
    {
        return [
            'with default arguments' => [
                ['index', 'synonymGroupId', null, 'term1', 'term2'],
                ['index', 'synonymGroupId', 'term1', 'term2'],
            ],
            'with SKIPINITIALSCAN modifier' => [
                ['index', 'synonymGroupId', (new SynUpdateArguments())->skipInitialScan(), 'term1', 'term2'],
                ['index', 'synonymGroupId', 'SKIPINITIALSCAN', 'term1', 'term2'],
            ],
        ];
    }
}
