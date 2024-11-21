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

use Predis\Response\ServerException;
use UnexpectedValueException;

class ZINTERCARD_Test extends PredisCommandTestCase
{
    /**
     * {@inheritDoc}
     */
    protected function getExpectedCommand(): string
    {
        return ZINTERCARD::class;
    }

    /**
     * {@inheritDoc}
     */
    protected function getExpectedId(): string
    {
        return 'ZINTERCARD';
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
        $this->assertSame(1, $this->getCommand()->parseResponse(1));
    }

    /**
     * @group connected
     * @dataProvider sortedSetsProvider
     * @param  array $firstSortedSet
     * @param  array $secondSortedSet
     * @param  int   $limit
     * @param  int   $expectedResponse
     * @return void
     * @requiresRedisVersion >= 7.0.0
     */
    public function testReturnsIntersectionCardinalityOnSortedSets(
        array $firstSortedSet,
        array $secondSortedSet,
        int $limit,
        int $expectedResponse
    ): void {
        $redis = $this->getClient();

        $redis->zadd('test-zintercard1', ...$firstSortedSet);
        $redis->zadd('test-zintercard2', ...$secondSortedSet);

        $this->assertSame($expectedResponse, $redis->zintercard(['test-zintercard1', 'test-zintercard2'], $limit));
    }

    /**
     * @group connected
     * @return void
     * @requiresRedisVersion >= 7.0.0
     */
    public function testReturnsIntersectionCardinalityZeroOnEmptySortedSetGiven(): void
    {
        $redis = $this->getClient();
        $sortedSet = [1, 'member1', 2, 'member2', 3, 'member3'];

        $redis->zadd('test-zintercard', ...$sortedSet);

        $this->assertSame(0, $redis->zintercard(['test-zintercard', 'non-existing-key']));
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 7.0.0
     */
    public function testThrowsExceptionOnWrongType(): void
    {
        $this->expectException(ServerException::class);
        $this->expectExceptionMessage('Operation against a key holding the wrong kind of value');

        $redis = $this->getClient();

        $redis->set('foo', 'bar');
        $redis->zintercard(['foo']);
    }

    /**
     * @group connected
     * @dataProvider unexpectedValuesProvider
     * @param         $keys
     * @param         $limit
     * @param  string $expectedExceptionMessage
     * @return void
     * @requiresRedisVersion >= 7.0.0
     */
    public function testThrowsExceptionOnUnexpectedValueGiven(
        $keys,
        $limit,
        string $expectedExceptionMessage
    ): void {
        $redis = $this->getClient();
        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage($expectedExceptionMessage);

        $redis->zintercard($keys, $limit);
    }

    public function argumentsProvider(): array
    {
        return [
            'with required arguments only' => [
                [['key1', 'key2']],
                [2, 'key1', 'key2'],
            ],
            'with all arguments' => [
                [['key1', 'key2'], 2],
                [2, 'key1', 'key2', 'LIMIT', 2],
            ],
        ];
    }

    public function sortedSetsProvider(): array
    {
        return [
            'with full intersection' => [
                [1, 'member1', 2, 'member2', 3, 'member3'],
                [1, 'member1', 2, 'member2', 3, 'member3'],
                0,
                3,
            ],
            'with partial intersection' => [
                [1, 'member1', 2, 'member2', 3, 'member3'],
                [1, 'member1', 2, 'member2', 4, 'member4'],
                0,
                2,
            ],
            'with no intersection' => [
                [1, 'member1', 2, 'member2', 3, 'member3'],
                [4, 'member4', 5, 'member5', 6, 'member6'],
                0,
                0,
            ],
            'with full intersection and limit' => [
                [1, 'member1', 2, 'member2', 3, 'member3'],
                [1, 'member1', 2, 'member2', 3, 'member3'],
                1,
                1,
            ],
        ];
    }

    public function unexpectedValuesProvider(): array
    {
        return [
            'with wrong type keys argument' => [
                'wrong',
                0,
                'Wrong keys argument type or position offset',
            ],
            'with wrong type limit argument' => [
                ['key1', 'key'],
                [1],
                'Wrong limit argument type',
            ],
        ];
    }
}
