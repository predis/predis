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
class SDIFFCARD_Test extends PredisCommandTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getExpectedCommand(): string
    {
        return SDIFFCARD::class;
    }

    /**
     * {@inheritdoc}
     */
    protected function getExpectedId(): string
    {
        return 'SDIFFCARD';
    }

    /**
     * @group disconnected
     * @dataProvider argumentsProvider
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
     * @dataProvider setsProvider
     * @param  array $sets
     * @param  array $keys
     * @param  int   $limit
     * @param  int   $expectedCardinality
     * @return void
     * @requiresRedisVersion >= 8.9.0
     */
    public function testReturnsCorrectCardinalityOfGivenSetDifference(
        array $sets,
        array $keys,
        int $limit,
        int $expectedCardinality
    ): void {
        $redis = $this->getClient();

        foreach ($sets as $set) {
            $redis->sadd(...$set);
        }

        $this->assertSame($expectedCardinality, $redis->sdiffcard($keys, $limit));
    }

    /**
     * @group connected
     * @return void
     * @requiresRedisVersion >= 8.9.0
     */
    public function testReturnsCorrectCardinalityOfGivenSetDifferenceResp3(): void
    {
        $redis = $this->getResp3Client();

        $redis->sadd('key1', 'member1', 'member2', 'member3');
        $redis->sadd('key2', 'member1');

        $this->assertSame(2, $redis->sdiffcard(['key1', 'key2']));
    }

    /**
     * @group connected
     * @dataProvider unexpectedValuesProvider
     * @param  array  $arguments
     * @param  string $expectedExceptionMessage
     * @return void
     * @requiresRedisVersion >= 8.9.0
     */
    public function testThrowsExceptionOnUnexpectedValuesGiven(
        array $arguments,
        string $expectedExceptionMessage
    ): void {
        $redis = $this->getClient();

        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage($expectedExceptionMessage);

        $redis->sdiffcard(...$arguments);
    }

    /**
     * @group connected
     * @return void
     * @requiresRedisVersion >= 8.9.0
     */
    public function testThrowsExceptionOnWrongType(): void
    {
        $this->expectException(ServerException::class);
        $this->expectExceptionMessage('Operation against a key holding the wrong kind of value');

        $redis = $this->getClient();

        $redis->set('sdiffcard', 'a');
        $redis->sdiffcard(['sdiffcard']);
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
            'with default arguments' => [
                [['key1', 'key2'], 0],
                [2, 'key1', 'key2', 'LIMIT', 0],
            ],
            'with non-default LIMIT' => [
                [['key1', 'key2'], 2],
                [2, 'key1', 'key2', 'LIMIT', 2],
            ],
        ];
    }

    public function setsProvider(): array
    {
        return [
            'with single key' => [
                [['key1', 'member1', 'member2', 'member3']],
                ['key1'],
                0,
                3,
            ],
            'with full difference' => [
                [
                    ['key1', 'member1', 'member2', 'member3'],
                    ['key2', 'member4', 'member5', 'member6'],
                ],
                ['key1', 'key2'],
                0,
                3,
            ],
            'with partial difference' => [
                [
                    ['key1', 'member1', 'member2', 'member3'],
                    ['key2', 'member1', 'member4', 'member5'],
                ],
                ['key1', 'key2'],
                0,
                2,
            ],
            'with no difference' => [
                [
                    ['key1', 'member1', 'member2', 'member3'],
                    ['key2', 'member1', 'member2', 'member3'],
                ],
                ['key1', 'key2'],
                0,
                0,
            ],
            'with multiple subtrahend sets' => [
                [
                    ['key1', 'member1', 'member2', 'member3', 'member4', 'member5'],
                    ['key2', 'member3', 'member4', 'member6'],
                    ['key3', 'member5', 'member7'],
                ],
                ['key1', 'key2', 'key3'],
                0,
                2,
            ],
            'with non-existing first key' => [
                [['key1', 'member1', 'member2', 'member3']],
                ['key2', 'key1'],
                0,
                0,
            ],
            'with non-existing subtrahend key' => [
                [['key1', 'member1', 'member2', 'member3']],
                ['key1', 'key2'],
                0,
                3,
            ],
            'with non-default LIMIT' => [
                [
                    ['key1', 'member1', 'member2', 'member3'],
                    ['key2', 'member4'],
                ],
                ['key1', 'key2'],
                1,
                1,
            ],
        ];
    }

    public function unexpectedValuesProvider(): array
    {
        return [
            'with wrong keys argument' => [
                ['key1', 0],
                'Wrong keys argument type or position offset',
            ],
            'with wrong limit argument' => [
                [['key1'], 'wrong'],
                'Wrong limit argument type',
            ],
        ];
    }
}
