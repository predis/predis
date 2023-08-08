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
class FTALIASDEL_Test extends PredisCommandTestCase
{
    /**
     * {@inheritDoc}
     */
    protected function getExpectedCommand(): string
    {
        return FTALIASDEL::class;
    }

    /**
     * {@inheritDoc}
     */
    protected function getExpectedId(): string
    {
        return 'FTALIASDEL';
    }

    /**
     * @group disconnected
     */
    public function testFilterArguments(): void
    {
        $actualArguments = ['alias'];
        $expectedArguments = ['alias'];

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
    public function testRemovesAliasFromGivenIndex(): void
    {
        $redis = $this->getClient();

        $schema = [new TextField('text_field')];

        $this->assertEquals('OK', $redis->ftcreate('index', $schema));
        $this->assertEquals('OK', $redis->ftaliasadd('alias', 'index'));
        $this->assertEquals('OK', $redis->ftaliasdel('alias'));
    }

    /**
     * @group connected
     * @return void
     * @requiresRediSearchVersion >= 2.8.0
     */
    public function testRemovesAliasFromGivenIndexResp3(): void
    {
        $redis = $this->getResp3Client();

        $schema = [new TextField('text_field')];

        $this->assertEquals('OK', $redis->ftcreate('index', $schema));
        $this->assertEquals('OK', $redis->ftaliasadd('alias', 'index'));
        $this->assertEquals('OK', $redis->ftaliasdel('alias'));
    }

    /**
     * @group connected
     * @group relay-incompatible
     * @return void
     * @requiresRediSearchVersion >= 1.0.0
     */
    public function testThrowsExceptionOnNonExistingAlias(): void
    {
        $redis = $this->getClient();

        $this->expectException(ServerException::class);
        $this->expectExceptionMessage('Alias does not exist');

        $redis->ftaliasdel('alias');
    }
}
