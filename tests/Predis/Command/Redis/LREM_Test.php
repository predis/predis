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
class LREM_Test extends PredisCommandTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getExpectedCommand(): string
    {
        return 'Predis\Command\Redis\LREM';
    }

    /**
     * {@inheritdoc}
     */
    protected function getExpectedId(): string
    {
        return 'LREM';
    }

    /**
     * @group disconnected
     */
    public function testFilterArguments(): void
    {
        $arguments = array('key', 1, 'value');
        $expected = array('key', 1, 'value');

        $command = $this->getCommand();
        $command->setArguments($arguments);

        $this->assertSame($expected, $command->getArguments());
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
     */
    public function testRemovesMatchingElementsFromHeadToTail(): void
    {
        $redis = $this->getClient();

        $redis->rpush('letters', 'a', '_', 'b', '_', 'c', '_', 'd', '_');

        $this->assertSame(2, $redis->lrem('letters', 2, '_'));
        $this->assertSame(array('a', 'b', 'c', '_', 'd', '_'), $redis->lrange('letters', 0, -1));
    }

    /**
     * @group connected
     */
    public function testRemovesMatchingElementsFromTailToHead(): void
    {
        $redis = $this->getClient();

        $redis->rpush('letters', 'a', '_', 'b', '_', 'c', '_', 'd', '_');

        $this->assertSame(2, $redis->lrem('letters', -2, '_'));
        $this->assertSame(array('a', '_', 'b', '_', 'c', 'd'), $redis->lrange('letters', 0, -1));
    }

    /**
     * @group connected
     */
    public function testRemovesAllMatchingElements(): void
    {
        $redis = $this->getClient();

        $redis->rpush('letters', 'a', '_', 'b', '_', 'c', '_', 'd', '_');

        $this->assertSame(4, $redis->lrem('letters', 0, '_'));
        $this->assertSame(array('a', 'b', 'c', 'd'), $redis->lrange('letters', 0, -1));
    }

    /**
     * @group connected
     */
    public function testReturnsZeroOnNonMatchingElementsOrEmptyList(): void
    {
        $redis = $this->getClient();

        $redis->rpush('letters', 'a', 'b', 'c', 'd');

        $this->assertSame(0, $redis->lrem('letters', 0, 'z'));
        $this->assertSame(0, $redis->lrem('digits', 0, 100));
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
        $redis->lrem('metavars', 0, 0);
    }
}
