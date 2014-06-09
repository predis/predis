<?php

/*
 * This file is part of the Predis package.
 *
 * (c) Daniele Alessandri <suppakilla@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Command;

/**
 * @group commands
 * @group realm-zset
 */
class ZSetRemoveRangeByLexTest extends PredisCommandTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getExpectedCommand()
    {
        return 'Predis\Command\ZSetRemoveRangeByLex';
    }

    /**
     * {@inheritdoc}
     */
    protected function getExpectedId()
    {
        return 'ZREMRANGEBYLEX';
    }

    /**
     * @group disconnected
     */
    public function testFilterArguments()
    {
        $arguments = array('key', '[a', '[b');
        $expected = array('key', '[a', '[b');

        $command = $this->getCommand();
        $command->setArguments($arguments);

        $this->assertSame($expected, $command->getArguments());
    }

    /**
     * @group disconnected
     */
    public function testParseResponse()
    {
        $this->assertSame(1, $this->getCommand()->parseResponse(1));
    }

    /**
     * @group disconnected
     */
    public function testPrefixKeys()
    {
        $arguments = array('key', '[a', '[b');
        $expected = array('prefix:key', '[a', '[b');

        $command = $this->getCommandWithArgumentsArray($arguments);
        $command->prefixKeys('prefix:');

        $this->assertSame($expected, $command->getArguments());
    }

    /**
     * @group disconnected
     */
    public function testPrefixKeysIgnoredOnEmptyArguments()
    {
        $command = $this->getCommand();
        $command->prefixKeys('prefix:');

        $this->assertSame(array(), $command->getArguments());
    }

    /**
     * @group connected
     */
    public function testRemovesRangeByLexWithWholeRange()
    {
        $this->markTestSkippedOnRedisVersionBelow('2.8.9', 'Lexicographical operations on sorted sets require Redis >= 2.8.9.', true);

        $redis = $this->getClient();

        $redis->zadd('letters', 0, 'a', 0, 'b', 0, 'c', 0, 'd', 0, 'e', 0, 'f', 0, 'g');

        $this->assertSame(0, $redis->zremrangebylex('letters', '+', '-'));
        $this->assertSame(7, $redis->zremrangebylex('letters', '-', '+'));

        $this->assertSame(array(), $redis->zrange('letters', 0, -1));
    }

    /**
     * @group connected
     */
    public function testRemovesRangeByLexWithInclusiveRange()
    {
        $this->markTestSkippedOnRedisVersionBelow('2.8.9', 'Lexicographical operations on sorted sets require Redis >= 2.8.9.', true);

        $redis = $this->getClient();

        $redis->zadd('letters', 0, 'a', 0, 'b', 0, 'c', 0, 'd', 0, 'e', 0, 'f', 0, 'g');

        $this->assertSame(3, $redis->zremrangebylex('letters', '[b', '[d'));
        $this->assertSame(array('a', 'e', 'f', 'g'), $redis->zrange('letters', 0, -1));
    }

    /**
     * @group connected
     */
    public function testRemovesRangeByLexWithExclusiveRange()
    {
        $this->markTestSkippedOnRedisVersionBelow('2.8.9', 'Lexicographical operations on sorted sets require Redis >= 2.8.9.', true);

        $redis = $this->getClient();

        $redis->zadd('letters', 0, 'a', 0, 'b', 0, 'c', 0, 'd', 0, 'e', 0, 'f', 0, 'g');

        $this->assertSame(3, $redis->zremrangebylex('letters', '(a', '(e'));
        $this->assertSame(array('a', 'e', 'f', 'g'), $redis->zrange('letters', 0, -1));
    }

    /**
     * @group connected
     */
    public function testRemovesRangeByLexWithMixedRange()
    {
        $this->markTestSkippedOnRedisVersionBelow('2.8.9', 'Lexicographical operations on sorted sets require Redis >= 2.8.9.', true);

        $redis = $this->getClient();

        $redis->zadd('letters', 0, 'a', 0, 'b', 0, 'c', 0, 'd', 0, 'e', 0, 'f', 0, 'g');

        $this->assertSame(3, $redis->zremrangebylex('letters', '[b', '(e'));
        $this->assertSame(array('a', 'e', 'f', 'g'), $redis->zrange('letters', 0, -1));
    }

    /**
     * @group connected
     * @expectedException Predis\ServerException
     * @expectedExceptionMessage min or max not valid string range item
     */
    public function testThrowsExceptionOnInvalidRangeFormat()
    {
        $this->markTestSkippedOnRedisVersionBelow('2.8.9', 'Lexicographical operations on sorted sets require Redis >= 2.8.9.', true);

        $redis = $this->getClient();

        $redis->zadd('letters', 0, 'a', 0, 'b', 0, 'c', 0, 'd', 0, 'e', 0, 'f', 0, 'g');
        $redis->zremrangebylex('letters', 'b', 'f');
    }

    /**
     * @group connected
     * @expectedException Predis\ServerException
     * @expectedExceptionMessage Operation against a key holding the wrong kind of value
     */
    public function testThrowsExceptionOnWrongType()
    {
        $this->markTestSkippedOnRedisVersionBelow('2.8.9', 'Lexicographical operations on sorted sets require Redis >= 2.8.9.', true);

        $redis = $this->getClient();

        $redis->set('foo', 'bar');
        $redis->zremrangebylex('foo', '[a', '[b');
    }
}
