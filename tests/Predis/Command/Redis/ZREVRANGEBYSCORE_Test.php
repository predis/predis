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

/**
 * @group commands
 * @group realm-zset
 */
class ZREVRANGEBYSCORE_Test extends PredisCommandTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getExpectedCommand(): string
    {
        return 'Predis\Command\Redis\ZREVRANGEBYSCORE';
    }

    /**
     * {@inheritdoc}
     */
    protected function getExpectedId(): string
    {
        return 'ZREVRANGEBYSCORE';
    }

    /**
     * @group disconnected
     */
    public function testFilterArguments(): void
    {
        $modifiers = array(
            'withscores' => true,
            'limit' => array(0, 100),
        );

        $arguments = array('zset', 0, 100, $modifiers);
        $expected = array('zset', 0, 100, 'LIMIT', 0, 100, 'WITHSCORES');

        $command = $this->getCommand();
        $command->setArguments($arguments);

        $this->assertSame($expected, $command->getArguments());
    }

    /**
     * @group disconnected
     */
    public function testFilterArgumentsWithStringWithscores(): void
    {
        $arguments = array('zset', 0, 100, 'withscores');
        $expected = array('zset', 0, 100, 'WITHSCORES');

        $command = $this->getCommand();
        $command->setArguments($arguments);

        $this->assertSame($expected, $command->getArguments());
    }

    /**
     * @group disconnected
     */
    public function testFilterArgumentsWithNamedLimit(): void
    {
        $arguments = array('zset', 0, 100, array('limit' => array('offset' => 1, 'count' => 2)));
        $expected = array('zset', 0, 100, 'LIMIT', 1, 2);

        $command = $this->getCommand();
        $command->setArguments($arguments);

        $this->assertSame($expected, $command->getArguments());
    }

    /**
     * @group disconnected
     */
    public function testParseResponse(): void
    {
        $raw = array('element1', 'element2', 'element3');
        $expected = array('element1', 'element2', 'element3');

        $command = $this->getCommand();

        $this->assertSame($expected, $command->parseResponse($raw));
    }

    /**
     * @group disconnected
     */
    public function testParseResponseWithScores(): void
    {
        $raw = array('element1', '1', 'element2', '2', 'element3', '3');
        $expected = array('element1' => '1', 'element2' => '2', 'element3' => '3');

        $command = $this->getCommandWithArgumentsArray(array('zset', 0, 1, 'withscores'));

        $this->assertSame($expected, $command->parseResponse($raw));
    }

    /**
     * @group disconnected
     */
    public function testAddsWithscoresModifiersOnlyWhenOptionIsTrue(): void
    {
        $command = $this->getCommandWithArguments('zset', 0, 100, array('withscores' => true));
        $this->assertSame(array('zset', 0, 100, 'WITHSCORES'), $command->getArguments());

        $command = $this->getCommandWithArguments('zset', 0, 100, array('withscores' => 1));
        $this->assertSame(array('zset', 0, 100, 'WITHSCORES'), $command->getArguments());

        $command = $this->getCommandWithArguments('zset', 0, 100, array('withscores' => false));
        $this->assertSame(array('zset', 0, 100), $command->getArguments());

        $command = $this->getCommandWithArguments('zset', 0, 100, array('withscores' => 0));
        $this->assertSame(array('zset', 0, 100), $command->getArguments());
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 2.2.0
     */
    public function testReturnsElementsInScoreRange(): void
    {
        $redis = $this->getClient();

        $redis->zadd('letters', -10, 'a', 0, 'b', 10, 'c', 20, 'd', 20, 'e', 30, 'f');

        $this->assertSame(array('a'), $redis->zrevrangebyscore('letters', -10, -10));
        $this->assertSame(array(), $redis->zrevrangebyscore('letters', 10, 30));
        $this->assertSame(array('e', 'd'), $redis->zrevrangebyscore('letters', 20, 20));
        $this->assertSame(array('f', 'e', 'd', 'c', 'b'), $redis->zrevrangebyscore('letters', 30, 0));

        $this->assertSame(array(), $redis->zrevrangebyscore('unknown', 0, 30));
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 2.2.0
     */
    public function testInfinityScoreIntervals(): void
    {
        $redis = $this->getClient();

        $redis->zadd('letters', -10, 'a', 0, 'b', 10, 'c', 20, 'd', 20, 'e', 30, 'f');

        $this->assertSame(array('f', 'e', 'd'), $redis->zrevrangebyscore('letters', '+inf', 15));
        $this->assertSame(array('c', 'b', 'a'), $redis->zrevrangebyscore('letters', 15, '-inf'));
        $this->assertSame(array('f', 'e', 'd', 'c', 'b', 'a'), $redis->zrevrangebyscore('letters', '+inf', '-inf'));
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 2.2.0
     */
    public function testExclusiveScoreIntervals(): void
    {
        $redis = $this->getClient();

        $redis->zadd('letters', -10, 'a', 0, 'b', 10, 'c', 20, 'd', 20, 'e', 30, 'f');

        $this->assertSame(array('e', 'd', 'c'), $redis->zrevrangebyscore('letters', '(30', 10));
        $this->assertSame(array('f', 'e', 'd'), $redis->zrevrangebyscore('letters', 30, '(10'));
        $this->assertSame(array('e', 'd'), $redis->zrevrangebyscore('letters', '(30', '(10'));
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 2.2.0
     */
    public function testRangeWithWithscoresModifier(): void
    {
        $redis = $this->getClient();

        $redis->zadd('letters', -10, 'a', 0, 'b', 10, 'c', 20, 'd', 20, 'e', 30, 'f');
        $expected = array('e' => '20', 'd' => '20', 'c' => '10');

        $this->assertSame($expected, $redis->zrevrangebyscore('letters', 20, 10, 'withscores'));
        $this->assertSame($expected, $redis->zrevrangebyscore('letters', 20, 10, array('withscores' => true)));
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 2.2.0
     */
    public function testRangeWithLimitModifier(): void
    {
        $redis = $this->getClient();

        $redis->zadd('letters', -10, 'a', 0, 'b', 10, 'c', 20, 'd', 20, 'e', 30, 'f');
        $expected = array('d', 'c');

        $this->assertSame($expected, $redis->zrevrangebyscore('letters', 20, 10, array('limit' => array(1, 2))));
        $this->assertSame($expected, $redis->zrevrangebyscore('letters', 20, 10, array('limit' => array('offset' => 1, 'count' => 2))));
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 2.2.0
     */
    public function testRangeWithCombinedModifiers(): void
    {
        $redis = $this->getClient();

        $redis->zadd('letters', -10, 'a', 0, 'b', 10, 'c', 20, 'd', 20, 'e', 30, 'f');

        $options = array('limit' => array(1, 2), 'withscores' => true);
        $expected = array('d' => '20', 'c' => '10');

        $this->assertSame($expected, $redis->zrevrangebyscore('letters', 20, 10, $options));
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 2.2.0
     */
    public function testThrowsExceptionOnWrongType(): void
    {
        $this->expectException('Predis\Response\ServerException');
        $this->expectExceptionMessage('Operation against a key holding the wrong kind of value');

        $redis = $this->getClient();

        $redis->set('foo', 'bar');
        $redis->zrevrangebyscore('foo', 0, 10);
    }
}
