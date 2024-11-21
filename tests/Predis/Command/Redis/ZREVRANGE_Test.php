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
class ZREVRANGE_Test extends PredisCommandTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getExpectedCommand(): string
    {
        return 'Predis\Command\Redis\ZREVRANGE';
    }

    /**
     * {@inheritdoc}
     */
    protected function getExpectedId(): string
    {
        return 'ZREVRANGE';
    }

    /**
     * @group disconnected
     */
    public function testFilterArguments(): void
    {
        $arguments = ['zset', 0, 100, ['withscores' => true]];
        $expected = ['zset', 0, 100, 'WITHSCORES'];

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
    public function testReturnsElementsInRange(): void
    {
        $redis = $this->getClient();

        $redis->zadd('letters', -10, 'a', 0, 'b', 10, 'c', 20, 'd', 20, 'e', 30, 'f');

        $this->assertSame([], $redis->zrevrange('letters', 1, 0));
        $this->assertSame(['f'], $redis->zrevrange('letters', 0, 0));
        $this->assertSame(['f', 'e', 'd', 'c'], $redis->zrevrange('letters', 0, 3));

        $this->assertSame(['f', 'e', 'd', 'c', 'b', 'a'], $redis->zrevrange('letters', 0, -1));
        $this->assertSame(['f', 'e', 'd'], $redis->zrevrange('letters', 0, -4));
        $this->assertSame(['d'], $redis->zrevrange('letters', 2, -4));
        $this->assertSame(['f', 'e', 'd', 'c', 'b', 'a'], $redis->zrevrange('letters', -100, 100));

        $this->assertSame([], $redis->zrevrange('unknown', 0, 30));
    }

    /**
     * @group connected
     */
    public function testRangeWithWithscoresModifier(): void
    {
        $redis = $this->getClient();

        $redis->zadd('letters', -10, 'a', 0, 'b', 10, 'c', 20, 'd', 20, 'e', 30, 'f');
        $expected = ['d' => '20', 'c' => '10', 'b' => '0'];

        $this->assertEquals($expected, $redis->zrevrange('letters', 2, 4, 'withscores'));
        $this->assertEquals($expected, $redis->zrevrange('letters', 2, 4, ['withscores' => true]));
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
        $redis->zrevrange('foo', 0, 10);
    }
}
