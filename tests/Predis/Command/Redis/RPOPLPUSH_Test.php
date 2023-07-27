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
 * @group realm-list
 */
class RPOPLPUSH_Test extends PredisCommandTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getExpectedCommand(): string
    {
        return 'Predis\Command\Redis\RPOPLPUSH';
    }

    /**
     * {@inheritdoc}
     */
    protected function getExpectedId(): string
    {
        return 'RPOPLPUSH';
    }

    /**
     * @group disconnected
     */
    public function testFilterArguments(): void
    {
        $arguments = ['key:source', 'key:destination'];
        $expected = ['key:source', 'key:destination'];

        $command = $this->getCommand();
        $command->setArguments($arguments);

        $this->assertSame($expected, $command->getArguments());
    }

    /**
     * @group disconnected
     */
    public function testParseResponse(): void
    {
        $this->assertSame('element', $this->getCommand()->parseResponse('element'));
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
        $expectedArguments = ['prefix:arg1', 'prefix:arg2', 'prefix:arg3', 'prefix:arg4'];

        $command->setArguments($actualArguments);
        $command->prefixKeys($prefix);

        $this->assertSame($expectedArguments, $command->getArguments());
    }

    /**
     * @group connected
     */
    public function testReturnsElementPoppedFromSourceAndPushesToDestination(): void
    {
        $redis = $this->getClient();

        $redis->rpush('letters:source', 'a', 'b', 'c');

        $this->assertSame('c', $redis->rpoplpush('letters:source', 'letters:destination'));
        $this->assertSame('b', $redis->rpoplpush('letters:source', 'letters:destination'));
        $this->assertSame('a', $redis->rpoplpush('letters:source', 'letters:destination'));

        $this->assertSame([], $redis->lrange('letters:source', 0, -1));
        $this->assertSame(['a', 'b', 'c'], $redis->lrange('letters:destination', 0, -1));
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 6.0.0
     */
    public function testReturnsElementPoppedFromSourceAndPushesToDestinationResp3(): void
    {
        $redis = $this->getResp3Client();

        $redis->rpush('letters:source', 'a', 'b', 'c');

        $this->assertSame('c', $redis->rpoplpush('letters:source', 'letters:destination'));
        $this->assertSame('b', $redis->rpoplpush('letters:source', 'letters:destination'));
        $this->assertSame('a', $redis->rpoplpush('letters:source', 'letters:destination'));

        $this->assertSame([], $redis->lrange('letters:source', 0, -1));
        $this->assertSame(['a', 'b', 'c'], $redis->lrange('letters:destination', 0, -1));
    }

    /**
     * @group connected
     */
    public function testReturnsElementPoppedFromSourceAndPushesToSelf(): void
    {
        $redis = $this->getClient();

        $redis->rpush('letters:source', 'a', 'b', 'c');

        $this->assertSame('c', $redis->rpoplpush('letters:source', 'letters:source'));
        $this->assertSame('b', $redis->rpoplpush('letters:source', 'letters:source'));
        $this->assertSame('a', $redis->rpoplpush('letters:source', 'letters:source'));

        $this->assertSame(['a', 'b', 'c'], $redis->lrange('letters:source', 0, -1));
    }

    /**
     * @group connected
     */
    public function testReturnsNullOnEmptySource(): void
    {
        $redis = $this->getClient();

        $this->assertNull($redis->rpoplpush('key:source', 'key:destination'));
    }

    /**
     * @group connected
     */
    public function testThrowsExceptionOnWrongTypeOfSourceKey(): void
    {
        $this->expectException('Predis\Response\ServerException');
        $this->expectExceptionMessage('Operation against a key holding the wrong kind of value');

        $redis = $this->getClient();

        $redis->set('key:source', 'foo');
        $redis->rpoplpush('key:source', 'key:destination');
    }

    /**
     * @group connected
     */
    public function testThrowsExceptionOnWrongTypeOfDestinationKey(): void
    {
        $this->expectException('Predis\Response\ServerException');
        $this->expectExceptionMessage('Operation against a key holding the wrong kind of value');

        $redis = $this->getClient();

        $redis->rpush('key:source', 'foo');
        $redis->set('key:destination', 'bar');

        $redis->rpoplpush('key:source', 'key:destination');
    }
}
