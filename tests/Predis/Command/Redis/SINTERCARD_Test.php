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
 * @group realm-set
 */
class SINTERCARD_Test extends PredisCommandTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getExpectedCommand(): string
    {
        return SINTERCARD::class;
    }

    /**
     * {@inheritdoc}
     */
    protected function getExpectedId(): string
    {
        return 'SINTERCARD';
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
     * @param  array $firstSet
     * @param  array $secondSet
     * @param  array $keys
     * @param  int   $limit
     * @param  int   $expectedCardinality
     * @return void
     * @requiresRedisVersion >= 7.0.0
     */
    public function testReturnsCorrectCardinalityOfGivenSetIntersection(
        array $firstSet,
        array $secondSet,
        array $keys,
        int $limit,
        int $expectedCardinality
    ): void {
        $redis = $this->getClient();

        $redis->sadd(...$firstSet);
        $redis->sadd(...$secondSet);

        $this->assertSame($expectedCardinality, $redis->sintercard($keys, $limit));
    }

    /**
     * @group connected
     * @dataProvider unexpectedValuesProvider
     * @param  array  $arguments
     * @param  string $expectedExceptionMessage
     * @return void
     * @requiresRedisVersion >= 7.0.0
     */
    public function testThrowsExceptionOnUnexpectedValuesGiven(
        array $arguments,
        string $expectedExceptionMessage
    ): void {
        $redis = $this->getClient();

        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage($expectedExceptionMessage);

        $redis->sintercard(...$arguments);
    }

    /**
     * @group connected
     * @return void
     * @requiresRedisVersion >= 7.0.0
     */
    public function testThrowsExceptionOnWrongType(): void
    {
        $this->expectException(ServerException::class);
        $this->expectExceptionMessage('Operation against a key holding the wrong kind of value');

        $redis = $this->getClient();

        $redis->set('sintercard', 'a');
        $redis->sintercard(['sintercard']);
    }

    public function argumentsProvider(): array
    {
        return [
            'with required arguments' => [
                [['key1', 'key2']],
                [2, 'key1', 'key2'],
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
            'with full intersection' => [
                ['key1', 'member1', 'member2', 'member3'],
                ['key2', 'member1', 'member2', 'member3'],
                ['key1', 'key2'],
                0,
                3,
            ],
            'with partial intersection' => [
                ['key1', 'member1', 'member2', 'member3'],
                ['key2', 'member1', 'member4', 'member5'],
                ['key1', 'key2'],
                0,
                1,
            ],
            'with no intersection' => [
                ['key1', 'member1', 'member2', 'member3'],
                ['key2', 'member4', 'member5', 'member6'],
                ['key1', 'key2'],
                0,
                0,
            ],
            'with no intersection on non-existing key' => [
                ['key1', 'member1', 'member2', 'member3'],
                ['key2', 'member1', 'member2', 'member3'],
                ['key1', 'key3'],
                0,
                0,
            ],
            'with non-default LIMIT' => [
                ['key1', 'member1', 'member2', 'member3'],
                ['key2', 'member1', 'member2', 'member3'],
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
