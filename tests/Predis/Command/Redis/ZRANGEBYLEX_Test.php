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
class ZRANGEBYLEX_Test extends PredisCommandTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getExpectedCommand(): string
    {
        return 'Predis\Command\Redis\ZRANGEBYLEX';
    }

    /**
     * {@inheritdoc}
     */
    protected function getExpectedId(): string
    {
        return 'ZRANGEBYLEX';
    }

    /**
     * @group disconnected
     */
    public function testFilterArguments(): void
    {
        $modifiers = [
            'limit' => [0, 100],
        ];

        $arguments = ['zset', '[a', '[z', $modifiers];
        $expected = ['zset', '[a', '[z', 'LIMIT', 0, 100];

        $command = $this->getCommand();
        $command->setArguments($arguments);

        $this->assertSame($expected, $command->getArguments());
    }

    /**
     * @group disconnected
     */
    public function testFilterArgumentsWithNamedLimit(): void
    {
        $arguments = ['zset', '[a', '[z', ['limit' => ['offset' => 1, 'count' => 2]]];
        $expected = ['zset', '[a', '[z', 'LIMIT', 1, 2];

        $command = $this->getCommand();
        $command->setArguments($arguments);

        $this->assertSame($expected, $command->getArguments());
    }

    /**
     * @group disconnected
     */
    public function testParseResponse(): void
    {
        $raw = ['a', 'b', 'c'];
        $expected = ['a', 'b', 'c'];

        $command = $this->getCommand();

        $this->assertSame($expected, $command->parseResponse($raw));
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 2.8.9
     */
    public function testReturnsElementsInWholeRange(): void
    {
        $redis = $this->getClient();

        $redis->zadd('letters', 0, 'a', 0, 'b', 0, 'c', 0, 'd', 0, 'e', 0, 'f', 0, 'g');

        $this->assertSame(['a', 'b', 'c', 'd', 'e', 'f', 'g'], $redis->zrangebylex('letters', '-', '+'));
        $this->assertSame([], $redis->zrangebylex('letters', '+', '-'));
        $this->assertSame([], $redis->zrangebylex('unknown', '-', '+'));
        $this->assertSame([], $redis->zrangebylex('unknown', '+', '-'));
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 2.8.9
     */
    public function testReturnsElementsInInclusiveRange(): void
    {
        $redis = $this->getClient();

        $redis->zadd('letters', 0, 'a', 0, 'b', 0, 'c', 0, 'd', 0, 'e', 0, 'f', 0, 'g');

        $this->assertSame(['a'], $redis->zrangebylex('letters', '[a', '[a'));
        $this->assertSame(['c', 'd', 'e', 'f'], $redis->zrangebylex('letters', '[c', '[f'));
        $this->assertSame(['a', 'b', 'c'], $redis->zrangebylex('letters', '-', '[c'));
        $this->assertSame([], $redis->zrangebylex('letters', '+', '[c'));
        $this->assertSame([], $redis->zrangebylex('letters', '[x', '[z'));
        $this->assertSame([], $redis->zrangebylex('unknown', '[0', '[1'));
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 2.8.9
     */
    public function testReturnsElementsInExclusiveRange(): void
    {
        $redis = $this->getClient();

        $redis->zadd('letters', 0, 'a', 0, 'b', 0, 'c', 0, 'd', 0, 'e', 0, 'f', 0, 'g');

        $this->assertSame([], $redis->zrangebylex('letters', '(a', '(a'));
        $this->assertSame(['d', 'e'], $redis->zrangebylex('letters', '(c', '(f'));
        $this->assertSame(['a', 'b'], $redis->zrangebylex('letters', '-', '(c'));
        $this->assertSame([], $redis->zrangebylex('letters', '+', '(c'));
        $this->assertSame([], $redis->zrangebylex('letters', '(x', '(z'));
        $this->assertSame([], $redis->zrangebylex('unknown', '(0', '(1'));
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 2.8.9
     */
    public function testReturnsElementsInMixedRange(): void
    {
        $redis = $this->getClient();

        $redis->zadd('letters', 0, 'a', 0, 'b', 0, 'c', 0, 'd', 0, 'e', 0, 'f', 0, 'g');

        $this->assertSame([], $redis->zrangebylex('letters', '[a', '(a'));
        $this->assertSame([], $redis->zrangebylex('letters', '(a', '[a'));
        $this->assertSame(['c', 'd', 'e'], $redis->zrangebylex('letters', '[c', '(f'));
        $this->assertSame(['d', 'e', 'f'], $redis->zrangebylex('letters', '(c', '[f'));
        $this->assertSame([], $redis->zrangebylex('unknown', '[0', '(5'));
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 2.8.9
     */
    public function testRangeWithLimitModifier(): void
    {
        $redis = $this->getClient();

        $redis->zadd('letters', 0, 'a', 0, 'b', 0, 'c', 0, 'd', 0, 'e', 0, 'f', 0, 'g');

        $this->assertSame(['c', 'd', 'e'], $redis->zrangebylex('letters', '-', '+', 'LIMIT', '2', '3'));
        $this->assertSame(['c', 'd', 'e'], $redis->zrangebylex('letters', '-', '+', ['limit' => [2, 3]]));
        $this->assertSame(['c', 'd', 'e'], $redis->zrangebylex('letters', '-', '+', ['limit' => ['offset' => 2, 'count' => 3]]));
        $this->assertSame([], $redis->zrangebylex('letters', '[a', '[f', 'LIMIT', '2', '0'));
        $this->assertSame([], $redis->zrangebylex('letters', '[a', '[f', 'LIMIT', '-4', '2'));
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
        $redis->zrangebylex('letters', 'b', 'f');
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
        $redis->zrangebylex('foo', '-', '+');
    }
}
