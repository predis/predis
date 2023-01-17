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
class ZREVRANGEBYLEX_Test extends PredisCommandTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getExpectedCommand(): string
    {
        return 'Predis\Command\Redis\ZREVRANGEBYLEX';
    }

    /**
     * {@inheritdoc}
     */
    protected function getExpectedId(): string
    {
        return 'ZREVRANGEBYLEX';
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

        $this->assertSame(['g', 'f', 'e', 'd', 'c', 'b', 'a'], $redis->zrevrangebylex('letters', '+', '-'));
        $this->assertSame([], $redis->zrevrangebylex('letters', '-', '+'));
        $this->assertSame([], $redis->zrevrangebylex('unknown', '-', '+'));
        $this->assertSame([], $redis->zrevrangebylex('unknown', '+', '-'));
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 2.8.9
     */
    public function testReturnsElementsInInclusiveRange(): void
    {
        $redis = $this->getClient();

        $redis->zadd('letters', 0, 'a', 0, 'b', 0, 'c', 0, 'd', 0, 'e', 0, 'f', 0, 'g');

        $this->assertSame(['a'], $redis->zrevrangebylex('letters', '[a', '[a'));
        $this->assertSame(['f', 'e', 'd', 'c'], $redis->zrevrangebylex('letters', '[f', '[c'));
        $this->assertSame(['g', 'f', 'e'], $redis->zrevrangebylex('letters', '+', '[e'));
        $this->assertSame([], $redis->zrevrangebylex('letters', '-', '[c'));
        $this->assertSame([], $redis->zrevrangebylex('letters', '[z', '[x'));
        $this->assertSame([], $redis->zrevrangebylex('unknown', '[1', '[0'));
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 2.8.9
     */
    public function testReturnsElementsInExclusiveRange(): void
    {
        $redis = $this->getClient();

        $redis->zadd('letters', 0, 'a', 0, 'b', 0, 'c', 0, 'd', 0, 'e', 0, 'f', 0, 'g');

        $this->assertSame([], $redis->zrevrangebylex('letters', '(a', '(a'));
        $this->assertSame(['e', 'd'], $redis->zrevrangebylex('letters', '(f', '(c'));
        $this->assertSame(['g', 'f'], $redis->zrevrangebylex('letters', '+', '(e'));
        $this->assertSame([], $redis->zrevrangebylex('letters', '-', '(c'));
        $this->assertSame([], $redis->zrevrangebylex('letters', '(z', '(x'));
        $this->assertSame([], $redis->zrevrangebylex('unknown', '(1', '(0'));
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 2.8.9
     */
    public function testReturnsElementsInMixedRange(): void
    {
        $redis = $this->getClient();

        $redis->zadd('letters', 0, 'a', 0, 'b', 0, 'c', 0, 'd', 0, 'e', 0, 'f', 0, 'g');

        $this->assertSame([], $redis->zrevrangebylex('letters', '[a', '(a'));
        $this->assertSame([], $redis->zrevrangebylex('letters', '(a', '[a'));
        $this->assertSame(['f', 'e', 'd'], $redis->zrevrangebylex('letters', '[f', '(c'));
        $this->assertSame(['e', 'd', 'c'], $redis->zrevrangebylex('letters', '(f', '[c'));
        $this->assertSame([], $redis->zrevrangebylex('unknown', '[5', '(0'));
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 2.8.9
     */
    public function testRangeWithLimitModifier(): void
    {
        $redis = $this->getClient();

        $redis->zadd('letters', 0, 'a', 0, 'b', 0, 'c', 0, 'd', 0, 'e', 0, 'f', 0, 'g');

        $this->assertSame(['e', 'd', 'c'], $redis->zrevrangebylex('letters', '+', '-', 'LIMIT', '2', '3'));
        $this->assertSame(['e', 'd', 'c'], $redis->zrevrangebylex('letters', '+', '-', ['limit' => [2, 3]]));
        $this->assertSame(['e', 'd', 'c'], $redis->zrevrangebylex('letters', '+', '-', ['limit' => ['offset' => 2, 'count' => 3]]));
        $this->assertSame([], $redis->zrevrangebylex('letters', '[f', '[a', 'LIMIT', '2', '0'));
        $this->assertSame([], $redis->zrevrangebylex('letters', '[f', '[a', 'LIMIT', '-4', '2'));
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
        $redis->zrevrangebylex('letters', 'f', 'b');
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
        $redis->zrevrangebylex('foo', '+', '-');
    }
}
