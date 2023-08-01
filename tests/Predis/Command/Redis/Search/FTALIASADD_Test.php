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
use Predis\Command\Argument\Search\SchemaFields\TextField;
use Predis\Command\Redis\PredisCommandTestCase;
use Predis\Response\ServerException;

/**
 * @group commands
 * @group realm-stack
 */
class FTALIASADD_Test extends PredisCommandTestCase
{
    /**
     * {@inheritDoc}
     */
    protected function getExpectedCommand(): string
    {
        return FTALIASADD::class;
    }

    /**
     * {@inheritDoc}
     */
    protected function getExpectedId(): string
    {
        return 'FTALIASADD';
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
    public function testAddAliasToGivenIndex(): void
    {
        $redis = $this->getClient();

        $arguments = new CreateArguments();
        $arguments->prefix(['prefix:']);
        $arguments->language();

        $schema = [new TextField('text_field')];

        $createResponse = $redis->ftcreate('index', $schema, $arguments);
        $this->assertEquals('OK', $createResponse);

        $actualResponse = $redis->ftaliasadd('alias', 'index');
        $this->assertEquals('OK', $actualResponse);
    }

    /**
     * @group connected
     * @return void
     * @requiresRediSearchVersion >= 2.8.0
     */
    public function testAddAliasToGivenIndexResp3(): void
    {
        $redis = $this->getResp3Client();

        $arguments = new CreateArguments();
        $arguments->prefix(['prefix:']);
        $arguments->language();

        $schema = [new TextField('text_field')];

        $createResponse = $redis->ftcreate('index', $schema, $arguments);
        $this->assertEquals('OK', $createResponse);

        $actualResponse = $redis->ftaliasadd('alias', 'index');
        $this->assertEquals('OK', $actualResponse);
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

        $redis->ftaliasadd('alias', 'index');
    }

    /**
     * @group connected
     * @group relay-incompatible
     * @return void
     * @requiresRediSearchVersion >= 1.0.0
     */
    public function testThrowsExceptionOnAlreadyExistingAlias(): void
    {
        $redis = $this->getClient();

        $arguments = new CreateArguments();
        $arguments->prefix(['prefix:']);
        $arguments->language();

        $schema = [new TextField('text_field')];

        $createResponse = $redis->ftcreate('index', $schema, $arguments);
        $this->assertEquals('OK', $createResponse);

        $actualResponse = $redis->ftaliasadd('alias', 'index');
        $this->assertEquals('OK', $actualResponse);

        $this->expectException(ServerException::class);
        $this->expectExceptionMessage('Alias already exists');

        $redis->ftaliasadd('alias', 'index');
    }
}
