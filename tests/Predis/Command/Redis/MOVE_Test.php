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

namespace Predis\Command\Redis;

use Predis\Command\PrefixableCommand;

/**
 * @group commands
 * @group realm-key
 */
class MOVE_Test extends PredisCommandTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getExpectedCommand(): string
    {
        return 'Predis\Command\Redis\MOVE';
    }

    /**
     * {@inheritdoc}
     */
    protected function getExpectedId(): string
    {
        return 'MOVE';
    }

    /**
     * @group disconnected
     */
    public function testFilterArguments(): void
    {
        $arguments = ['key', 10];
        $expected = ['key', 10];

        $command = $this->getCommand();
        $command->setArguments($arguments);

        $this->assertSame($expected, $command->getArguments());
    }

    /**
     * @group disconnected
     */
    public function testParseResponse(): void
    {
        $command = $this->getCommand();

        $this->assertSame(0, $command->parseResponse(0));
        $this->assertSame(1, $command->parseResponse(1));
    }

    /**
     * @group disconnected
     */
    public function testPrefixKeys(): void
    {
        /** @var PrefixableCommand $command */
        $command = $this->getCommand();
        $actualArguments = ['arg1', 'arg2', 'arg3', 'arg4'];
        $prefix = 'prefix:';
        $expectedArguments = ['prefix:arg1', 'arg2', 'arg3', 'arg4'];

        $command->setArguments($actualArguments);
        $command->prefixKeys($prefix);

        $this->assertSame($expectedArguments, $command->getArguments());
    }

    /**
     * @group connected
     *
     * @todo Should be improved, this test fails when REDIS_SERVER_DBNUM is 0.
     */
    public function testMovesKeysToDifferentDatabases(): void
    {
        $db = REDIS_SERVER_DBNUM + 1;
        $redis = $this->getClient();

        $redis->set('foo', 'bar');

        $this->assertSame(1, $redis->move('foo', $db));
        $this->assertSame(0, $redis->exists('foo'));

        $redis->select($db);
        $this->assertSame(1, $redis->exists('foo'));

        $redis->del('foo');
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 6.0.0
     */
    public function testMovesKeysToDifferentDatabasesResp3(): void
    {
        $db = REDIS_SERVER_DBNUM + 1;
        $redis = $this->getResp3Client();

        $redis->set('foo', 'bar');

        $this->assertSame(1, $redis->move('foo', $db));
        $this->assertSame(0, $redis->exists('foo'));

        $redis->select($db);
        $this->assertSame(1, $redis->exists('foo'));

        $redis->del('foo');
    }

    /**
     * @group connected
     */
    public function testThrowsExceptionOnInvalidDatabases(): void
    {
        $this->expectException('Predis\Response\ServerException');
        $this->expectExceptionMessageMatches('/ERR.*out of range/');

        $redis = $this->getClient();

        $redis->set('foo', 'bar');

        $redis->move('foo', 100000000);
    }
}
