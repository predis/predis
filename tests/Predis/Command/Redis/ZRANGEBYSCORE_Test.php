<?php

/*
 * This file is part of the Predis package.
 *
 * (c) 2009-2020 Daniele Alessandri
 * (c) 2021-2024 Till Krüss
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Command\Redis;

/**
 * @group commands
 * @group realm-zset
 */
class ZRANGEBYSCORE_Test extends PredisCommandTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getExpectedCommand(): string
    {
        return 'Predis\Command\Redis\ZRANGEBYSCORE';
    }

    /**
     * {@inheritdoc}
     */
    protected function getExpectedId(): string
    {
        return 'ZRANGEBYSCORE';
    }

    /**
     * @group disconnected
     */
    public function testFilterArguments(): void
    {
        $modifiers = [
            'withscores' => true,
            'limit' => [0, 100],
        ];

        $arguments = ['zset', 0, 100, $modifiers];
        $expected = ['zset', 0, 100, 'LIMIT', 0, 100, 'WITHSCORES'];

        $command = $this->getCommand();
        $command->setArguments($arguments);

        $this->assertSame($expected, $command->getArguments());
    }

    /**
     * @group disconnected
     */
    public function testFilterArgumentsWithStringWithscores(): void
    {
        $arguments = ['zset', 0, 100, 'withscores'];
        $expected = ['zset', 0, 100, 'WITHSCORES'];

        $command = $this->getCommand();
        $command->setArguments($arguments);

        $this->assertSame($expected, $command->getArguments());
    }

    /**
     * @group disconnected
     */
    public function testFilterArgumentsWithNamedLimit(): void
    {
        $arguments = ['zset', 0, 100, ['limit' => ['offset' => 1, 'count' => 2]]];
        $expected = ['zset', 0, 100, 'LIMIT', 1, 2];

        $command = $this->getCommand();
        $command->setArguments($arguments);

        $this->assertSame($expected, $command->getArguments());
    }

    /**
     * @group disconnected
     */
    public function testParseResponse(): void
    {
        $raw = ['element1', 'element2', 'element3'];
        $expected = ['element1', 'element2', 'element3'];

        $command = $this->getCommand();

        $this->assertSame($expected, $command->parseResponse($raw));
    }

    /**
     * @group disconnected
     */
    public function testParseResponseWithScores(): void
    {
        $raw = ['element1', '1', 'element2', '2', 'element3', '3'];
        $expected = ['element1' => '1', 'element2' => '2', 'element3' => '3'];

        $command = $this->getCommandWithArgumentsArray(['zset', 0, 1, 'withscores']);

        $this->assertSame($expected, $command->parseResponse($raw));
    }

    /**
     * @group disconnected
     */
    public function testAddsWithscoresModifiersOnlyWhenOptionIsTrue(): void
    {
        $command = $this->getCommandWithArguments('zset', 0, 100, ['withscores' => true]);
        $this->assertSame(['zset', 0, 100, 'WITHSCORES'], $command->getArguments());

        $command = $this->getCommandWithArguments('zset', 0, 100, ['withscores' => 1]);
        $this->assertSame(['zset', 0, 100, 'WITHSCORES'], $command->getArguments());

        $command = $this->getCommandWithArguments('zset', 0, 100, ['withscores' => false]);
        $this->assertSame(['zset', 0, 100], $command->getArguments());

        $command = $this->getCommandWithArguments('zset', 0, 100, ['withscores' => 0]);
        $this->assertSame(['zset', 0, 100], $command->getArguments());
    }

    /**
     * @group connected
     */
    public function testReturnsElementsInScoreRange(): void
    {
        $redis = $this->getClient();

        $redis->zadd('letters', -10, 'a', 0, 'b', 10, 'c', 20, 'd', 20, 'e', 30, 'f');

        $this->assertSame(['a'], $redis->zrangebyscore('letters', -10, -10));
        $this->assertSame(['c', 'd', 'e', 'f'], $redis->zrangebyscore('letters', 10, 30));
        $this->assertSame(['d', 'e'], $redis->zrangebyscore('letters', 20, 20));
        $this->assertSame([], $redis->zrangebyscore('letters', 30, 0));

        $this->assertSame([], $redis->zrangebyscore('unknown', 0, 30));
    }

    /**
     * @group connected
     */
    public function testInfinityScoreIntervals(): void
    {
        $redis = $this->getClient();

        $redis->zadd('letters', -10, 'a', 0, 'b', 10, 'c', 20, 'd', 20, 'e', 30, 'f');

        $this->assertSame(['a', 'b', 'c'], $redis->zrangebyscore('letters', '-inf', 15));
        $this->assertSame(['d', 'e', 'f'], $redis->zrangebyscore('letters', 15, '+inf'));
        $this->assertSame(['a', 'b', 'c', 'd', 'e', 'f'], $redis->zrangebyscore('letters', '-inf', '+inf'));
    }

    /**
     * @group connected
     */
    public function testExclusiveScoreIntervals(): void
    {
        $redis = $this->getClient();

        $redis->zadd('letters', -10, 'a', 0, 'b', 10, 'c', 20, 'd', 20, 'e', 30, 'f');

        $this->assertSame(['c', 'd', 'e'], $redis->zrangebyscore('letters', 10, '(30'));
        $this->assertSame(['d', 'e', 'f'], $redis->zrangebyscore('letters', '(10', 30));
        $this->assertSame(['d', 'e'], $redis->zrangebyscore('letters', '(10', '(30'));
    }

    /**
     * @group connected
     */
    public function testRangeWithWithscoresModifier(): void
    {
        $redis = $this->getClient();

        $redis->zadd('letters', -10, 'a', 0, 'b', 10, 'c', 20, 'd', 20, 'e', 30, 'f');
        $expected = ['c' => '10', 'd' => '20', 'e' => '20'];

        $this->assertEquals($expected, $redis->zrangebyscore('letters', 10, 20, 'withscores'));
        $this->assertEquals($expected, $redis->zrangebyscore('letters', 10, 20, ['withscores' => true]));
    }

    /**
     * @group connected
     */
    public function testRangeWithLimitModifier(): void
    {
        $redis = $this->getClient();

        $redis->zadd('letters', -10, 'a', 0, 'b', 10, 'c', 20, 'd', 20, 'e', 30, 'f');
        $expected = ['d', 'e'];

        $this->assertSame($expected, $redis->zrangebyscore('letters', 10, 20, ['limit' => [1, 2]]));
        $this->assertSame($expected, $redis->zrangebyscore('letters', 10, 20, ['limit' => ['offset' => 1, 'count' => 2]]));
    }

    /**
     * @group connected
     */
    public function testRangeWithCombinedModifiers(): void
    {
        $redis = $this->getClient();

        $redis->zadd('letters', -10, 'a', 0, 'b', 10, 'c', 20, 'd', 20, 'e', 30, 'f');

        $options = ['limit' => [1, 2], 'withscores' => true];
        $expected = ['d' => '20', 'e' => '20'];

        $this->assertEquals($expected, $redis->zrangebyscore('letters', 10, 20, $options));
    }

    /**
     * @group connected
     */
    public function testThrowsExceptionOnWrongType(): void
    {
        $this->expectException('Predis\Response\ServerException');
        $this->expectExceptionMessage('Operation against a key holding the wrong kind of value');

        $redis = $this->getClient();

        $redis->set('foo', 'bar');
        $redis->zrangebyscore('foo', 0, 10);
    }
}
