<?php

namespace Predis\Command\Redis;

use Predis\Response\ServerException;
use UnexpectedValueException;

/**
 * @group commands
 * @group realm-zset
 */
class ZUNION_Test extends PredisCommandTestCase
{
    /**
     * @inheritDoc
     */
    protected function getExpectedCommand(): string
    {
        return ZUNION::class;
    }

    /**
     * @inheritDoc
     */
    protected function getExpectedId(): string
    {
        return 'ZUNION';
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
     * @group connected
     * @dataProvider sortedSetsProvider
     * @param array $firstSortedSet
     * @param array $secondSortedSet
     * @param array $weights
     * @param string $aggregate
     * @param bool $withScores
     * @param array $expectedResponse
     * @return void
     * @requiresRedisVersion >= 6.2.0
     */
    public function testReturnsIntersectedValuesOnSortedSets(
        array $firstSortedSet,
        array $secondSortedSet,
        array $weights,
        string $aggregate,
        bool $withScores,
        array $expectedResponse
    ): void {
        $redis = $this->getClient();

        $redis->zadd('test-zunion1', ...$firstSortedSet);
        $redis->zadd('test-zunion2', ...$secondSortedSet);

        $actualResponse = $redis->zunion(
            ['test-zunion1', 'test-zunion2'],
            $weights,
            $aggregate,
            $withScores
        );

        $this->assertSame($expectedResponse, $actualResponse);
    }


    /**
     * @group connected
     * @requiresRedisVersion >= 6.2.0
     */
    public function testThrowsExceptionOnWrongType(): void
    {
        $this->expectException(ServerException::class);
        $this->expectExceptionMessage('Operation against a key holding the wrong kind of value');

        $redis = $this->getClient();

        $redis->set('foo', 'bar');
        $redis->zunion(['foo']);
    }

    /**
     * @dataProvider unexpectedValueProvider
     * @param $keys
     * @param $weights
     * @param string $aggregate
     * @param bool $withScores
     * @param string $expectedExceptionMessage
     * @return void
     * @requiresRedisVersion >= 6.2.0
     */
    public function testThrowsExceptionOnUnexpectedValueGiven(
        $keys,
        $weights,
        string $aggregate,
        bool $withScores,
        string $expectedExceptionMessage
    ): void {
        $redis = $this->getClient();
        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage($expectedExceptionMessage);

        $redis->zunion($keys, $weights, $aggregate, $withScores);
    }

    public function argumentsProvider(): array
    {
        return [
            'with required arguments only' => [
                [['key1', 'key2']],
                [2, 'key1', 'key2'],
            ],
            'with weights' => [
                [['key1', 'key2'], [1, 2]],
                [2, 'key1', 'key2', 'WEIGHTS', 1, 2],
            ],
            'with aggregate' => [
                [['key1', 'key2'], [], 'min'],
                [2, 'key1', 'key2', 'AGGREGATE', 'MIN'],
            ],
            'with withscores' => [
                [['key1', 'key2'], [], 'min', true],
                [2, 'key1', 'key2', 'AGGREGATE', 'MIN', 'WITHSCORES'],
            ],
            'with all arguments' => [
                [['key1', 'key2'], [1, 2], 'min', true],
                [ 2, 'key1', 'key2', 'WEIGHTS', 1, 2, 'AGGREGATE', 'MIN', 'WITHSCORES'],
            ]
        ];
    }

    public function sortedSetsProvider(): array
    {
        return [
            'with required arguments' => [
                [1, 'member1', 2, 'member2', 3, 'member3'],
                [1, 'member1', 2, 'member2'],
                [],
                'sum',
                false,
                ['member1', 'member3', 'member2'],
            ],
            'with weights and withscores' => [
                [1, 'member1', 2, 'member2', 3, 'member3'],
                [1, 'member1', 2, 'member2'],
                [2, 3],
                'sum',
                true,
                ['member1' => '5', 'member3' => '6', 'member2' => '10'],
            ],
            'with aggregate and withscores' => [
                [1, 'member1', 4, 'member2', 3, 'member3'],
                [2, 'member1', 2, 'member2'],
                [],
                'max',
                true,
                ['member1' => '2', 'member3' => '3', 'member2' => '4'],
            ],
            'with all arguments' => [
                [1, 'member1', 5, 'member2', 4, 'member3'],
                [2, 'member1', 2, 'member2'],
                [2, 3],
                'max',
                true,
                ['member1' => '6', 'member3' => '8', 'member2' => '10'],
            ],
        ];
    }

    public function unexpectedValueProvider(): array
    {
        return [
            'with unexpected keys argument' => [
                1,
                [],
                'sum',
                false,
                'Wrong keys argument type or position offset'
            ],
            'with unexpected weights argument' => [
                ['key1'],
                1,
                'sum',
                false,
                'Wrong weights argument type'
            ],
            'with unexpected aggregate argument' => [
                ['key1'],
                [],
                'wrong',
                false,
                'Aggregate argument accepts only: min, max, sum values'
            ],
        ];
    }
}
