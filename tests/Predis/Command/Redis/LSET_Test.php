<?php

/*
 * This file is part of the Predis package.
 *
 * (c) 2009-2020 Daniele Alessandri
 * (c) 2021-2025 Till Krüss
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Command\Redis;

use Predis\Command\PrefixableCommand;

/**
 * @group commands
 * @group realm-list
 */
class LSET_Test extends PredisCommandTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getExpectedCommand(): string
    {
        return 'Predis\Command\Redis\LSET';
    }

    /**
     * {@inheritdoc}
     */
    protected function getExpectedId(): string
    {
        return 'LSET';
    }

    /**
     * @group disconnected
     */
    public function testFilterArguments(): void
    {
        $arguments = ['key', 0, 'value'];
        $expected = ['key', 0, 'value'];

        $command = $this->getCommand();
        $command->setArguments($arguments);

        $this->assertSame($expected, $command->getArguments());
    }

    /**
     * @group disconnected
     */
    public function testParseResponse(): void
    {
        $this->assertSame('OK', $this->getCommand()->parseResponse('OK'));
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
     */
    public function testSetsElementAtSpecifiedIndex(): void
    {
        $redis = $this->getClient();

        $redis->rpush('letters', 'a', 'b', 'c');

        $this->assertEquals('OK', $redis->lset('letters', 1, 'B'));
        $this->assertSame(['a', 'B', 'c'], $redis->lrange('letters', 0, -1));
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 6.0.0
     */
    public function testSetsElementAtSpecifiedIndexResp3(): void
    {
        $redis = $this->getResp3Client();

        $redis->rpush('letters', 'a', 'b', 'c');

        $this->assertEquals('OK', $redis->lset('letters', 1, 'B'));
        $this->assertSame(['a', 'B', 'c'], $redis->lrange('letters', 0, -1));
    }

    /**
     * @group connected
     */
    public function testThrowsExceptionOnIndexOutOfRange(): void
    {
        $this->expectException('Predis\Response\ServerException');
        $this->expectExceptionMessage('ERR index out of range');

        $redis = $this->getClient();

        $redis->rpush('letters', 'a', 'b', 'c');
        $redis->lset('letters', 21, 'z');
    }

    /**
     * @group connected
     */
    public function testThrowsExceptionOnWrongType(): void
    {
        $this->expectException('Predis\Response\ServerException');
        $this->expectExceptionMessage('Operation against a key holding the wrong kind of value');

        $redis = $this->getClient();

        $redis->set('metavars', 'foo');
        $redis->lset('metavars', 0, 'hoge');
    }
}
