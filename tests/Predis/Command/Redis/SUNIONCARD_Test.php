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

use Predis\Response\ServerException;
use UnexpectedValueException;

/**
 * @group commands
 * @group realm-set
 */
class SUNIONCARD_Test extends PredisCommandTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getExpectedCommand(): string
    {
        return SUNIONCARD::class;
    }

    /**
     * {@inheritdoc}
     */
    protected function getExpectedId(): string
    {
        return 'SUNIONCARD';
    }

    /**
     * @group disconnected
     * @dataProvider argumentsProvider
     */
    public function testFilterArguments(array $actualArguments, array $expectedArguments): void
    {
        $command = $this->getCommand();
        $command->setArguments($actualArguments);

        $this->assertSameValues($expectedArguments, $command->getArguments());
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
     * @dataProvider setsProvider
     * @param  array $sets
     * @param  array $keys
     * @param  bool  $approx
     * @param  int   $limit
     * @param  int   $expectedCardinality
     * @return void
     * @requiresRedisVersion >= 8.10.0
     */
    public function testReturnsCorrectCardinalityOfGivenSetUnion(
        array $sets,
        array $keys,
        bool $approx,
        int $limit,
        int $expectedCardinality
    ): void {
        $redis = $this->getClient();

        foreach ($sets as $set) {
            $redis->sadd(...$set);
        }

        $this->assertSame($expectedCardinality, $redis->sunioncard($keys, $approx, $limit));
    }

    /**
     * @group connected
     * @return void
     * @requiresRedisVersion >= 8.10.0
     */
    public function testApproxReturnsSameResultAsExactMatchingOnSmallSets(): void
    {
        $redis = $this->getClient();

        $redis->sadd('key1', 'member1', 'member2', 'member3');
        $redis->sadd('key2', 'member3', 'member4');

        $this->assertSame(
            $redis->sunioncard(['key1', 'key2']),
            $redis->sunioncard(['key1', 'key2'], true)
        );
    }

    /**
     * @group connected
     * @return void
     * @requiresRedisVersion >= 8.10.0
     */
    public function testReturnsCorrectCardinalityOfGivenSetUnionResp3(): void
    {
        $redis = $this->getResp3Client();

        $redis->sadd('key1', 'member1', 'member2', 'member3');
        $redis->sadd('key2', 'member3', 'member4');

        $this->assertSame(4, $redis->sunioncard(['key1', 'key2']));
    }

    /**
     * @group connected
     * @dataProvider unexpectedValuesProvider
     * @param  array  $arguments
     * @param  string $expectedExceptionMessage
     * @return void
     * @requiresRedisVersion >= 8.10.0
     */
    public function testThrowsExceptionOnUnexpectedValuesGiven(
        array $arguments,
        string $expectedExceptionMessage
    ): void {
        $redis = $this->getClient();

        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage($expectedExceptionMessage);

        $redis->sunioncard(...$arguments);
    }

    /**
     * @group connected
     * @return void
     * @requiresRedisVersion >= 8.10.0
     */
    public function testThrowsExceptionOnWrongType(): void
    {
        $this->expectException(ServerException::class);
        $this->expectExceptionMessage('Operation against a key holding the wrong kind of value');

        $redis = $this->getClient();

        $redis->set('sunioncard', 'a');
        $redis->sunioncard(['sunioncard']);
    }

    public function argumentsProvider(): array
    {
        return [
            'with required arguments' => [
                [['key1', 'key2']],
                [2, 'key1', 'key2'],
            ],
            'with single key' => [
                [['key1']],
                [1, 'key1'],
            ],
            'with APPROX argument' => [
                [['key1', 'key2'], true],
                [2, 'key1', 'key2', 'APPROX'],
            ],
            'with default LIMIT' => [
                [['key1', 'key2'], false, 0],
                [2, 'key1', 'key2', 'LIMIT', 0],
            ],
            'with non-default LIMIT' => [
                [['key1', 'key2'], false, 2],
                [2, 'key1', 'key2', 'LIMIT', 2],
            ],
            'with APPROX and LIMIT arguments' => [
                [['key1', 'key2'], true, 2],
                [2, 'key1', 'key2', 'APPROX', 'LIMIT', 2],
            ],
        ];
    }

    public function setsProvider(): array
    {
        return [
            'with single key' => [
                [['key1', 'member1', 'member2', 'member3']],
                ['key1'],
                false,
                0,
                3,
            ],
            'with multiple keys - disjoint sets' => [
                [
                    ['key1', 'member1', 'member2', 'member3'],
                    ['key2', 'member4', 'member5', 'member6'],
                ],
                ['key1', 'key2'],
                false,
                0,
                6,
            ],
            'with multiple keys - overlapping sets' => [
                [
                    ['key1', 'member1', 'member2', 'member3'],
                    ['key2', 'member3', 'member4'],
                ],
                ['key1', 'key2'],
                false,
                0,
                4,
            ],
            'with multiple keys - equal sets' => [
                [
                    ['key1', 'member1', 'member2', 'member3'],
                    ['key2', 'member1', 'member2', 'member3'],
                ],
                ['key1', 'key2'],
                false,
                0,
                3,
            ],
            'with non-existing key' => [
                [['key1', 'member1', 'member2', 'member3']],
                ['key1', 'key2'],
                false,
                0,
                3,
            ],
            'with all non-existing keys' => [
                [['key1', 'member1', 'member2', 'member3']],
                ['key2', 'key3'],
                false,
                0,
                0,
            ],
            'with APPROX argument' => [
                [
                    ['key1', 'member1', 'member2', 'member3'],
                    ['key2', 'member3', 'member4'],
                ],
                ['key1', 'key2'],
                true,
                0,
                4,
            ],
            'with non-default LIMIT' => [
                [
                    ['key1', 'member1', 'member2', 'member3'],
                    ['key2', 'member4', 'member5'],
                ],
                ['key1', 'key2'],
                false,
                3,
                3,
            ],
            'with LIMIT higher than cardinality' => [
                [
                    ['key1', 'member1', 'member2', 'member3'],
                    ['key2', 'member4'],
                ],
                ['key1', 'key2'],
                false,
                100,
                4,
            ],
        ];
    }

    public function unexpectedValuesProvider(): array
    {
        return [
            'with wrong keys argument' => [
                ['key1', false, 0],
                'Wrong keys argument type or position offset',
            ],
            'with wrong approx argument' => [
                [['key1'], 'wrong'],
                'Wrong approx argument type',
            ],
            'with wrong limit argument' => [
                [['key1'], false, 'wrong'],
                'Wrong limit argument type',
            ],
        ];
    }
}
