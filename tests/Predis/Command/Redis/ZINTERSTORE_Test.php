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

/**
 * @group commands
 * @group realm-zset
 */
class ZINTERSTORE_Test extends PredisCommandTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getExpectedCommand(): string
    {
        return ZINTERSTORE::class;
    }

    /**
     * {@inheritdoc}
     */
    protected function getExpectedId(): string
    {
        return 'ZINTERSTORE';
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
     * @param  array  $firstSortedSet
     * @param  array  $secondSortedSet
     * @param  string $destination
     * @param  array  $weights
     * @param  string $aggregate
     * @param  int    $expectedResponse
     * @param  array  $expectedResultSortedSet
     * @return void
     * @requiresRedisVersion >= 2.0.0
     */
    public function testStoresIntersectedValuesOnSortedSets(
        array $firstSortedSet,
        array $secondSortedSet,
        string $destination,
        array $weights,
        string $aggregate,
        int $expectedResponse,
        array $expectedResultSortedSet
    ): void {
        $redis = $this->getClient();

        $redis->zadd('test-zunionstore1', ...$firstSortedSet);
        $redis->zadd('test-zunionstore2', ...$secondSortedSet);

        $actualResponse = $redis->zinterstore(
            $destination,
            ['test-zunionstore1', 'test-zunionstore2'],
            $weights,
            $aggregate
        );

        $this->assertSame($expectedResponse, $actualResponse);
        $this->assertEquals(
            $expectedResultSortedSet,
            $redis->zrange($destination, 0, -1, ['withscores' => true])
        );
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 2.0.0
     */
    public function testThrowsExceptionOnWrongType(): void
    {
        $this->expectException(ServerException::class);
        $this->expectExceptionMessage('Operation against a key holding the wrong kind of value');

        $redis = $this->getClient();

        $redis->set('foo', 'bar');
        $redis->zinterstore('zset_interstore:destination', ['foo']);
    }

    /**
     * @dataProvider unexpectedValueProvider
     * @param  string $destination
     * @param         $keys
     * @param         $weights
     * @param  string $aggregate
     * @param  string $expectedExceptionMessage
     * @return void
     */
    public function testThrowsExceptionOnUnexpectedValueGiven(
        string $destination,
        $keys,
        $weights,
        string $aggregate,
        string $expectedExceptionMessage
    ): void {
        $redis = $this->getClient();
        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage($expectedExceptionMessage);

        $redis->zinterstore($destination, $keys, $weights, $aggregate);
    }

    public function argumentsProvider(): array
    {
        return [
            'with required arguments only' => [
                ['destination', ['key1', 'key2']],
                ['destination', 2, 'key1', 'key2'],
            ],
            'with weights' => [
                ['destination', ['key1', 'key2'], [1, 2]],
                ['destination', 2, 'key1', 'key2', 'WEIGHTS', 1, 2],
            ],
            'with aggregate' => [
                ['destination', ['key1', 'key2'], [], 'min'],
                ['destination', 2, 'key1', 'key2', 'AGGREGATE', 'MIN'],
            ],
            'with all arguments' => [
                ['destination', ['key1', 'key2'], [1, 2], 'min'],
                ['destination', 2, 'key1', 'key2', 'WEIGHTS', 1, 2, 'AGGREGATE', 'MIN'],
            ],
            'with options array' => [
                ['destination', ['key1', 'key2'], [
                    'weights' => [1, 2],
                    'aggregate' => 'min',
                ]],
                ['destination', 2, 'key1', 'key2', 'WEIGHTS', 1, 2, 'AGGREGATE', 'MIN'],
            ],
        ];
    }

    public function sortedSetsProvider(): array
    {
        return [
            'with required arguments' => [
                [1, 'member1', 2, 'member2', 3, 'member3'],
                [1, 'member1', 2, 'member2'],
                'destination',
                [],
                'sum',
                2,
                ['member1' => '2', 'member2' => '4'],
            ],
            'with weights' => [
                [1, 'member1', 2, 'member2', 3, 'member3'],
                [1, 'member1', 2, 'member2'],
                'destination',
                [2, 3],
                'sum',
                2,
                ['member1' => '5', 'member2' => '10'],
            ],
            'with aggregate' => [
                [1, 'member1', 4, 'member2', 3, 'member3'],
                [2, 'member1', 2, 'member2'],
                'destination',
                [],
                'max',
                2,
                ['member1' => '2', 'member2' => '4'],
            ],
            'with all arguments' => [
                [1, 'member1', 5, 'member2', 4, 'member3'],
                [2, 'member1', 2, 'member2'],
                'destination',
                [2, 3],
                'max',
                2,
                ['member1' => '6', 'member2' => '10'],
            ],
        ];
    }

    public function unexpectedValueProvider(): array
    {
        return [
            'with unexpected keys argument' => [
                'destination',
                1,
                [],
                'sum',
                'Wrong keys argument type or position offset',
            ],
            'with unexpected weights argument' => [
                'destination',
                ['key1'],
                1,
                'sum',
                'Wrong weights argument type',
            ],
            'with unexpected aggregate argument' => [
                'destination',
                ['key1'],
                [],
                'wrong',
                'Aggregate argument accepts only: min, max, sum values',
            ],
        ];
    }
}
