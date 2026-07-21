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

use Predis\Command\Argument\Search\SchemaFields\TextField;
use Predis\Command\Redis\PredisCommandTestCase;
use Predis\Response\ServerException;

/**
 * @group commands
 * @group realm-stack
 */
class FTALIASLIST_Test extends PredisCommandTestCase
{
    /**
     * {@inheritDoc}
     */
    protected function getExpectedCommand(): string
    {
        return FTALIASLIST::class;
    }

    /**
     * {@inheritDoc}
     */
    protected function getExpectedId(): string
    {
        return 'FTALIASLIST';
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
        $response = ['alias1', 'alias2'];

        $this->assertSame($response, $this->getCommand()->parseResponse($response));
    }

    /**
     * @group connected
     * @group relay-resp3
     * @return void
     * @requiresRedisVersion >= 8.10.0
     */
    public function testReturnsAliasesAssociatedWithGivenIndex(): void
    {
        $redis = $this->getClient();

        $this->assertEquals('OK', $redis->ftcreate('index', [new TextField('field')]));
        $this->assertEquals('OK', $redis->ftaliasadd('alias1', 'index'));
        $this->assertEquals('OK', $redis->ftaliasadd('alias2', 'index'));

        $this->assertSameValues(['alias1', 'alias2'], $redis->ftaliaslist('index'));
    }

    /**
     * @group connected
     * @group relay-resp3
     * @return void
     * @requiresRedisVersion >= 8.10.0
     */
    public function testReturnsEmptyListForIndexWithoutAliases(): void
    {
        $redis = $this->getClient();

        $this->assertEquals('OK', $redis->ftcreate('index', [new TextField('field')]));

        $this->assertSame([], $redis->ftaliaslist('index'));
    }

    /**
     * @group connected
     * @return void
     * @requiresRedisVersion >= 8.10.0
     */
    public function testReturnsAliasesAssociatedWithGivenIndexResp3(): void
    {
        $redis = $this->getResp3Client();

        $this->assertEquals('OK', $redis->ftcreate('index', [new TextField('field')]));
        $this->assertEquals('OK', $redis->ftaliasadd('alias1', 'index'));
        $this->assertEquals('OK', $redis->ftaliasadd('alias2', 'index'));

        $this->assertSameValues(['alias1', 'alias2'], $redis->ftaliaslist('index'));
    }

    /**
     * @group connected
     * @group relay-resp3
     * @return void
     * @requiresRedisVersion >= 8.10.0
     */
    public function testThrowsExceptionOnNonExistingIndex(): void
    {
        $redis = $this->getClient();

        $this->expectException(ServerException::class);
        $this->expectExceptionMessage('Index not found');

        $redis->ftaliaslist('missing_index');
    }
}
