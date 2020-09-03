<?php

/*
 * This file is part of the Predis package.
 *
 * (c) Daniele Alessandri <suppakilla@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Command\Redis;

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
        $arguments = array('key:source', 'key:destination');
        $expected = array('key:source', 'key:destination');

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
     * @group connected
     */
    public function testReturnsElementPoppedFromSourceAndPushesToDestination(): void
    {
        $redis = $this->getClient();

        $redis->rpush('letters:source', 'a', 'b', 'c');

        $this->assertSame('c', $redis->rpoplpush('letters:source', 'letters:destination'));
        $this->assertSame('b', $redis->rpoplpush('letters:source', 'letters:destination'));
        $this->assertSame('a', $redis->rpoplpush('letters:source', 'letters:destination'));

        $this->assertSame(array(), $redis->lrange('letters:source', 0, -1));
        $this->assertSame(array('a', 'b', 'c'), $redis->lrange('letters:destination', 0, -1));
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

        $this->assertSame(array('a', 'b', 'c'), $redis->lrange('letters:source', 0, -1));
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
