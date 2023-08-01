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

use Predis\Command\Argument\Search\SchemaFields\TextField;
use Predis\Command\Redis\PredisCommandTestCase;
use Predis\Response\ServerException;

/**
 * @group commands
 * @group realm-stack
 */
class FTSYNDUMP_Test extends PredisCommandTestCase
{
    /**
     * {@inheritDoc}
     */
    protected function getExpectedCommand(): string
    {
        return FTSYNDUMP::class;
    }

    /**
     * {@inheritDoc}
     */
    protected function getExpectedId(): string
    {
        return 'FTSYNDUMP';
    }

    /**
     * @group disconnected
     */
    public function testFilterArguments(): void
    {
        $actualArguments = ['index'];
        $expectedArguments = ['index'];

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
     * @requiresRediSearchVersion >= 1.2.0
     */
    public function testDumpReturnsContentOfSynonymGroupFromGivenIndex(): void
    {
        $redis = $this->getClient();
        $expectedResponse = ['term1', ['synonym1'], 'term2', ['synonym1']];

        $this->assertEquals(
            'OK',
            $redis->ftcreate('index', [new TextField('text_field')])
        );

        $this->assertEquals(
            'OK',
            $redis->ftsynupdate('index', 'synonym1', null, 'term1', 'term2')
        );

        $this->assertSame($expectedResponse, $redis->ftsyndump('index'));
    }

    /**
     * @group connected
     * @return void
     * @requiresRediSearchVersion >= 2.8.0
     */
    public function testDumpReturnsContentOfSynonymGroupFromGivenIndexResp3(): void
    {
        $redis = $this->getResp3Client();
        $expectedResponse = ['term1' => ['synonym1'], 'term2' => ['synonym1']];

        $this->assertEquals(
            'OK',
            $redis->ftcreate('index', [new TextField('text_field')])
        );

        $this->assertEquals(
            'OK',
            $redis->ftsynupdate('index', 'synonym1', null, 'term1', 'term2')
        );

        $this->assertSame($expectedResponse, $redis->ftsyndump('index'));
    }

    /**
     * @group connected
     * @return void
     * @requiresRediSearchVersion >= 1.2.0
     */
    public function testThrowsExceptionOnNonExistingIndex(): void
    {
        $redis = $this->getClient();

        $this->expectException(ServerException::class);
        $this->expectExceptionMessage('Unknown index name');

        $redis->ftsyndump('index');
    }
}
