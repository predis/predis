<?php

/*
 * This file is part of the Predis package.
 *
 * (c) 2009-2020 Daniele Alessandri
 * (c) 2021-2026 Till Krüss
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Command\Redis;

use Predis\Command\PrefixableCommand;

/**
 * @group commands
 * @group realm-zset
 */
class ZRANGE_Test extends PredisCommandTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getExpectedCommand(): string
    {
        return 'Predis\Command\Redis\ZRANGE';
    }

    /**
     * {@inheritdoc}
     */
    protected function getExpectedId(): string
    {
        return 'ZRANGE';
    }

    /**
     * @dataProvider argumentsProvider
     * @group disconnected
     */
    public function testFilterArguments(array $actualArguments, array $expectedArguments): void
    {
        $command = $this->getCommand();
        $command->setArguments($actualArguments);

        $this->assertSame($expectedArguments, $command->getArguments());
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
    public function testThrowsExceptionWhenByscoreAndBylexAreBothSet(): void
    {
        $this->expectException('InvalidArgumentException');
        $this->expectExceptionMessage('BYSCORE and BYLEX are mutually exclusive');

        $command = $this->getCommand();
        $command->setArguments(['zset', 0, 100, ['byscore' => true, 'bylex' => true]]);
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
        $expectedArguments = ['prefix:arg1', 'arg2', 'arg3', 'arg4'];

        $command->setArguments($actualArguments);
        $command->prefixKeys($prefix);

        $this->assertSame($expectedArguments, $command->getArguments());
    }

    /**
     * @group connected
     */
    public function testReturnsElementsInRange(): void
    {
        $redis = $this->getClient();

        $redis->zadd('letters', -10, 'a', 0, 'b', 10, 'c', 20, 'd', 20, 'e', 30, 'f');

        $this->assertSame([], $redis->zrange('letters', 1, 0));
        $this->assertSame(['a'], $redis->zrange('letters', 0, 0));
        $this->assertSame(['a', 'b', 'c', 'd'], $redis->zrange('letters', 0, 3));

        $this->assertSame(['a', 'b', 'c', 'd', 'e', 'f'], $redis->zrange('letters', 0, -1));
        $this->assertSame(['a', 'b', 'c'], $redis->zrange('letters', 0, -4));
        $this->assertSame(['c'], $redis->zrange('letters', 2, -4));
        $this->assertSame(['a', 'b', 'c', 'd', 'e', 'f'], $redis->zrange('letters', -100, 100));

        $this->assertSame([], $redis->zrange('unknown', 0, 30));
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 6.0.0
     */
    public function testReturnsElementsInRangeResp3(): void
    {
        $redis = $this->getResp3Client();

        $redis->zadd('letters', -10, 'a', 0, 'b', 10, 'c', 20, 'd', 20, 'e', 30, 'f');

        $this->assertSame([], $redis->zrange('letters', 1, 0));
        $this->assertSame(['a'], $redis->zrange('letters', 0, 0));
        $this->assertSame(['a', 'b', 'c', 'd'], $redis->zrange('letters', 0, 3));

        $this->assertSame(['a', 'b', 'c', 'd', 'e', 'f'], $redis->zrange('letters', 0, -1));
        $this->assertSame(['a', 'b', 'c'], $redis->zrange('letters', 0, -4));
        $this->assertSame(['c'], $redis->zrange('letters', 2, -4));
        $this->assertSame(['a', 'b', 'c', 'd', 'e', 'f'], $redis->zrange('letters', -100, 100));

        $this->assertSame([], $redis->zrange('unknown', 0, 30));
    }

    /**
     * @group connected
     */
    public function testRangeWithWithscoresModifier(): void
    {
        $redis = $this->getClient();

        $redis->zadd('letters', -10, 'a', 0, 'b', 10, 'c', 20, 'd', 20, 'e', 30, 'f');
        $expected = ['c' => '10', 'd' => '20', 'e' => '20'];

        $this->assertEquals($expected, $redis->zrange('letters', 2, 4, 'withscores'));
        $this->assertEquals($expected, $redis->zrange('letters', 2, 4, ['withscores' => true]));
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 6.2.0
     */
    public function testRangeWithByscoreOption(): void
    {
        $redis = $this->getClient();

        $redis->zadd('scores', 10, 'a', 20, 'b', 30, 'c', 40, 'd', 50, 'e');

        // BYSCORE: get elements with scores between 20 and 40
        $result = $redis->zrange('scores', 20, 40, ['byscore' => true]);
        $this->assertSame(['b', 'c', 'd'], $result);

        // BYSCORE with WITHSCORES
        $resultWithScores = $redis->zrange('scores', 20, 40, ['byscore' => true, 'withscores' => true]);
        $this->assertEquals(['b' => '20', 'c' => '30', 'd' => '40'], $resultWithScores);
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 6.2.0
     */
    public function testRangeWithBylexOption(): void
    {
        $redis = $this->getClient();

        // All members have same score (0) for BYLEX to work
        $redis->zadd('names', 0, 'alice', 0, 'bob', 0, 'charlie', 0, 'david', 0, 'eve');

        // BYLEX: get elements lexicographically between [b and [d
        $result = $redis->zrange('names', '[bob', '[david', ['bylex' => true]);
        $this->assertSame(['bob', 'charlie', 'david'], $result);

        // BYLEX with open interval
        $resultOpen = $redis->zrange('names', '(bob', '(eve', ['bylex' => true]);
        $this->assertSame(['charlie', 'david'], $resultOpen);
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 6.2.0
     */
    public function testRangeWithRevOption(): void
    {
        $redis = $this->getClient();

        $redis->zadd('numbers', 1, 'one', 2, 'two', 3, 'three', 4, 'four', 5, 'five');

        // REV: reverse order
        $result = $redis->zrange('numbers', 0, 2, ['rev' => true]);
        $this->assertSame(['five', 'four', 'three'], $result);

        // REV with WITHSCORES
        $resultWithScores = $redis->zrange('numbers', 0, 2, ['rev' => true, 'withscores' => true]);
        $this->assertEquals(['five' => '5', 'four' => '4', 'three' => '3'], $resultWithScores);
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 6.2.0
     */
    public function testRangeWithLimitOption(): void
    {
        $redis = $this->getClient();

        $redis->zadd('items', 1, 'a', 2, 'b', 3, 'c', 4, 'd', 5, 'e', 6, 'f');

        // BYSCORE with LIMIT: skip 1, take 2
        $result = $redis->zrange('items', 2, 5, ['byscore' => true, 'limit' => [1, 2]]);
        $this->assertSame(['c', 'd'], $result);

        // BYSCORE with LIMIT and WITHSCORES
        $resultWithScores = $redis->zrange('items', 1, 6, ['byscore' => true, 'limit' => [0, 3], 'withscores' => true]);
        $this->assertEquals(['a' => '1', 'b' => '2', 'c' => '3'], $resultWithScores);
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 6.2.0
     */
    public function testRangeWithCombinedOptions(): void
    {
        $redis = $this->getClient();

        $redis->zadd('combined', 10, 'a', 20, 'b', 30, 'c', 40, 'd', 50, 'e', 60, 'f');

        // BYSCORE + REV + LIMIT + WITHSCORES
        // Note: When using REV with BYSCORE, the range must be reversed (max, min)
        $result = $redis->zrange('combined', 60, 20, [
            'byscore' => true,
            'rev' => true,
            'limit' => [1, 2],
            'withscores' => true,
        ]);

        // Should get elements with scores 60-20 in reverse order, skip 1, take 2
        // Reverse order: f(60), e(50), d(40), c(30), b(20)
        // Skip 1: e(50), d(40), c(30), b(20)
        // Take 2: e(50), d(40)
        $this->assertEquals(['e' => '50', 'd' => '40'], $result);
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
        $redis->zrange('foo', 0, 10);
    }

    public function argumentsProvider(): array
    {
        return [
            'with basic arguments only' => [
                ['zset', 0, 100],
                ['zset', 0, 100],
            ],
            'with WITHSCORES as string (backward compatibility)' => [
                ['zset', 0, 100, 'withscores'],
                ['zset', 0, 100, 'WITHSCORES'],
            ],
            'with WITHSCORES in options array' => [
                ['zset', 0, 100, ['withscores' => true]],
                ['zset', 0, 100, 'WITHSCORES'],
            ],
            'with WITHSCORES truthy value' => [
                ['zset', 0, 100, ['withscores' => 1]],
                ['zset', 0, 100, 'WITHSCORES'],
            ],
            'with WITHSCORES false' => [
                ['zset', 0, 100, ['withscores' => false]],
                ['zset', 0, 100],
            ],
            'with WITHSCORES zero' => [
                ['zset', 0, 100, ['withscores' => 0]],
                ['zset', 0, 100],
            ],
            'with BYSCORE' => [
                ['zset', 0, 100, ['byscore' => true]],
                ['zset', 0, 100, 'BYSCORE'],
            ],
            'with BYLEX' => [
                ['zset', '-', '+', ['bylex' => true]],
                ['zset', '-', '+', 'BYLEX'],
            ],
            'with REV' => [
                ['zset', 0, 100, ['rev' => true]],
                ['zset', 0, 100, 'REV'],
            ],
            'with LIMIT' => [
                ['zset', 0, 100, ['limit' => [10, 20]]],
                ['zset', 0, 100, 'LIMIT', 10, 20],
            ],
            'with BYSCORE and REV' => [
                ['zset', 0, 100, ['byscore' => true, 'rev' => true]],
                ['zset', 0, 100, 'BYSCORE', 'REV'],
            ],
            'with BYSCORE, REV and WITHSCORES' => [
                ['zset', 0, 100, ['byscore' => true, 'rev' => true, 'withscores' => true]],
                ['zset', 0, 100, 'BYSCORE', 'REV', 'WITHSCORES'],
            ],
            'with BYSCORE, LIMIT and WITHSCORES' => [
                ['zset', 0, 100, ['byscore' => true, 'limit' => [5, 10], 'withscores' => true]],
                ['zset', 0, 100, 'BYSCORE', 'LIMIT', 5, 10, 'WITHSCORES'],
            ],
            'with BYLEX, REV and LIMIT' => [
                ['zset', '[a', '[z', ['bylex' => true, 'rev' => true, 'limit' => [0, 10]]],
                ['zset', '[a', '[z', 'BYLEX', 'REV', 'LIMIT', 0, 10],
            ],
            'with all options except BYLEX' => [
                ['zset', 0, 100, ['byscore' => true, 'rev' => true, 'limit' => [0, 10], 'withscores' => true]],
                ['zset', 0, 100, 'BYSCORE', 'REV', 'LIMIT', 0, 10, 'WITHSCORES'],
            ],
        ];
    }
}
