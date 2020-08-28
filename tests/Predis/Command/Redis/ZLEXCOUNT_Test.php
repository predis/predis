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
 * @group realm-zset
 */
class ZLEXCOUNT_Test extends PredisCommandTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getExpectedCommand(): string
    {
        return 'Predis\Command\Redis\ZLEXCOUNT';
    }

    /**
     * {@inheritdoc}
     */
    protected function getExpectedId(): string
    {
        return 'ZLEXCOUNT';
    }

    /**
     * @group disconnected
     */
    public function testFilterArguments(): void
    {
        $arguments = array('key', '+', '-');
        $expected = array('key', '+', '-');

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
     * @requiresRedisVersion >= 2.8.9
     */
    public function testExclusiveIntervalRange(): void
    {
        $redis = $this->getClient();

        $redis->zadd('letters', 0, 'a', 0, 'b', 0, 'c', 0, 'd', 0, 'e', 0, 'f', 0, 'g');

        $this->assertSame(3, $redis->zlexcount('letters', '(b', '(f'));
        $this->assertSame(5, $redis->zlexcount('letters', '(b', '(z'));
        $this->assertSame(4, $redis->zlexcount('letters', '(0', '(e'));
        $this->assertSame(0, $redis->zlexcount('letters', '(f', '(b'));
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 2.8.9
     */
    public function testInclusiveIntervalRange(): void
    {
        $redis = $this->getClient();

        $redis->zadd('letters', 0, 'a', 0, 'b', 0, 'c', 0, 'd', 0, 'e', 0, 'f', 0, 'g');

        $this->assertSame(5, $redis->zlexcount('letters', '[b', '[f'));
        $this->assertSame(6, $redis->zlexcount('letters', '[b', '[z'));
        $this->assertSame(5, $redis->zlexcount('letters', '[0', '[e'));
        $this->assertSame(0, $redis->zlexcount('letters', '[f', '[b'));
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 2.8.9
     */
    public function testWholeRangeInterval(): void
    {
        $redis = $this->getClient();

        $redis->zadd('letters', 0, 'a', 0, 'b', 0, 'c', 0, 'd', 0, 'e', 0, 'f', 0, 'g');

        $this->assertSame(7, $redis->zlexcount('letters', '-', '+'));
        $this->assertSame(0, $redis->zlexcount('letters', '+', '-'));
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 2.8.9
     */
    public function testThrowsExceptionOnInvalidRangeFormat(): void
    {
        $this->expectException('Predis\Response\ServerException');
        $this->expectExceptionMessage('min or max not valid string range item');

        $redis = $this->getClient();

        $redis->zadd('letters', 0, 'a', 0, 'b', 0, 'c', 0, 'd', 0, 'e', 0, 'f', 0, 'g');
        $redis->zlexcount('letters', 'b', 'f');
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 2.8.9
     */
    public function testThrowsExceptionOnWrongType(): void
    {
        $this->expectException('Predis\Response\ServerException');
        $this->expectExceptionMessage('Operation against a key holding the wrong kind of value');

        $redis = $this->getClient();

        $redis->set('foo', 'bar');
        $redis->zlexcount('foo', '+', '-');
    }
}
