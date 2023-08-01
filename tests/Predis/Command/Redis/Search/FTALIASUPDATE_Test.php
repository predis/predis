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
class FTALIASUPDATE_Test extends PredisCommandTestCase
{
    /**
     * {@inheritDoc}
     */
    protected function getExpectedCommand(): string
    {
        return FTALIASUPDATE::class;
    }

    /**
     * {@inheritDoc}
     */
    protected function getExpectedId(): string
    {
        return 'FTALIASUPDATE';
    }

    /**
     * @group disconnected
     */
    public function testFilterArguments(): void
    {
        $actualArguments = ['alias', 'index'];
        $expectedArguments = ['alias', 'index'];

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
    public function testUpdateAliasAddAliasToGivenIndexIfAliasNotExists(): void
    {
        $redis = $this->getClient();

        $schema = [new TextField('text_field')];

        $createResponse = $redis->ftcreate('index', $schema);
        $this->assertEquals('OK', $createResponse);

        $actualResponse = $redis->ftaliasupdate('alias', 'index');
        $this->assertEquals('OK', $actualResponse);
    }

    /**
     * @group connected
     * @return void
     * @requiresRediSearchVersion >= 2.8.0
     */
    public function testUpdateAliasAddAliasToGivenIndexIfAliasNotExistsResp3(): void
    {
        $redis = $this->getResp3Client();

        $schema = [new TextField('text_field')];

        $createResponse = $redis->ftcreate('index', $schema);
        $this->assertEquals('OK', $createResponse);

        $actualResponse = $redis->ftaliasupdate('alias', 'index');
        $this->assertEquals('OK', $actualResponse);
    }

    /**
     * @group connected
     * @group relay-incompatible
     * @return void
     * @requiresRediSearchVersion >= 1.0.0
     */
    public function testUpdateRemovesAliasAssociationFromAlreadyExistingAlias(): void
    {
        $redis = $this->getClient();

        $schema = [new TextField('text_field')];

        $createResponse = $redis->ftcreate('index', $schema);
        $this->assertEquals('OK', $createResponse);

        $actualResponse = $redis->ftaliasadd('alias', 'index');
        $this->assertEquals('OK', $actualResponse);
        $this->assertEquals('OK', $redis->ftaliasupdate('new_alias', 'index'));
    }

    /**
     * @group connected
     * @group relay-incompatible
     * @return void
     * @requiresRediSearchVersion >= 1.0.0
     */
    public function testThrowsExceptionOnNonExistingIndex(): void
    {
        $redis = $this->getClient();

        $this->expectException(ServerException::class);
        $this->expectExceptionMessage('Unknown index name (or name is an alias itself)');

        $redis->ftaliasupdate('alias', 'index');
    }
}
