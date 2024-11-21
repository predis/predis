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

use Predis\Command\Argument\Server\LimitOffsetCount;
use UnexpectedValueException;

/**
 * @group commands
 * @group realm-set
 */
class SORT_RO_Test extends PredisCommandTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getExpectedCommand(): string
    {
        return SORT_RO::class;
    }

    /**
     * {@inheritdoc}
     */
    protected function getExpectedId(): string
    {
        return 'SORT_RO';
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
     * @dataProvider listProvider
     * @param  array $listArguments
     * @param  array $sortArguments
     * @param  array $expectedSortedResponse
     * @return void
     * @requiresRedisVersion >= 7.0.0
     */
    public function testSortItemsWithinGivenList(
        array $listArguments,
        array $sortArguments,
        array $expectedSortedResponse
    ): void {
        $redis = $this->getClient();

        $redis->lpush(...$listArguments);

        $this->assertSame($expectedSortedResponse, $redis->sort_ro(...$sortArguments));
    }

    /**
     * @group connected
     * @dataProvider listsProvider
     * @param  array $localKeys
     * @param  array $externalKeys
     * @param  array $sortArguments
     * @param  array $expectedSortedResponse
     * @return void
     * @requiresRedisVersion >= 7.0.0
     */
    public function testSortItemsWithExternalKeysWithinGivenList(
        array $localKeys,
        array $externalKeys,
        array $sortArguments,
        array $expectedSortedResponse
    ): void {
        $redis = $this->getClient();

        $redis->lpush(...$localKeys);
        $redis->mset(...$externalKeys);

        $this->assertSame($expectedSortedResponse, $redis->sort_ro(...$sortArguments));
    }

    /**
     * @group connected
     * @dataProvider unexpectedValuesProvider
     * @param  array  $arguments
     * @param  string $expectedExceptionMessage
     * @return void
     * @requiresRedisVersion >= 7.0.0
     */
    public function testThrowsExceptionOnUnexpectedValueGiven(
        array $arguments,
        string $expectedExceptionMessage
    ): void {
        $redis = $this->getClient();

        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage($expectedExceptionMessage);

        $redis->sort_ro(...$arguments);
    }

    public function argumentsProvider(): array
    {
        return [
            'with default arguments' => [
                ['key'],
                ['key'],
            ],
            'with BY argument' => [
                ['key', 'pattern'],
                ['key', 'BY', 'pattern'],
            ],
            'with LIMIT argument' => [
                ['key', null, new LimitOffsetCount(0, 1)],
                ['key', 'LIMIT', 0, 1],
            ],
            'with GET patterns' => [
                ['key', null, null, ['pattern1', 'pattern2']],
                ['key', 'GET', 'pattern1', 'GET', 'pattern2'],
            ],
            'with sorting argument - ASC' => [
                ['key', null, null, [], 'asc'],
                ['key', 'ASC'],
            ],
            'with sorting argument - DESC' => [
                ['key', null, null, [], 'desc'],
                ['key', 'DESC'],
            ],
            'with ALPHA argument' => [
                ['key', null, null, [], null, true],
                ['key', 'ALPHA'],
            ],
            'with all arguments argument' => [
                ['key', 'pattern', new LimitOffsetCount(0, 1), ['pattern1', 'pattern2'], 'asc', true],
                ['key', 'BY', 'pattern', 'LIMIT', 0, 1, 'GET', 'pattern1', 'GET', 'pattern2', 'ASC', 'ALPHA'],
            ],
        ];
    }

    public function listProvider(): array
    {
        return [
            'without any modifiers' => [
                ['key', 2, 1],
                ['key', null, null, [], null, false],
                ['1', '2'],
            ],
            'with LIMIT modifier' => [
                ['key', 2, 1, 4, 15, 3, 36],
                ['key', null, new LimitOffsetCount(1, 2), [], null, false],
                ['2', '3'],
            ],
            'with sorting - ASC' => [
                ['key', 2, 1],
                ['key', null, null, [], 'asc', false],
                ['1', '2'],
            ],
            'with sorting - DESC' => [
                ['key', 2, 1],
                ['key', null, null, [], 'desc', false],
                ['2', '1'],
            ],
            'with sorting lexicographically' => [
                ['key', 'abc', 'aab', 'abb'],
                ['key', null, null, [], null, true],
                ['aab', 'abb', 'abc'],
            ],
            'with all arguments for single list' => [
                ['key', 'abc', 'aab', 'abb'],
                ['key', null, new LimitOffsetCount(0, 2), [], 'desc', true],
                ['abc', 'abb'],
            ],
        ];
    }

    public function listsProvider(): array
    {
        return [
            'sorted by external keys - returns local keys' => [
                ['uid', 1, 2, 3, 4, 5],
                ['points_1', 500, 'points_2', 200, 'points_3', 300, 'points_4', 400, 'points_5', 100],
                ['uid', 'points_*', null, [], null, false],
                ['5', '2', '3', '4', '1'],
            ],
            'sorted by external keys - returns external keys' => [
                ['uid', 1, 2, 3, 4, 5],
                [
                    'points_1', 500, 'points_2', 200, 'points_3', 300, 'points_4', 400, 'points_5', 100,
                    'user_1', 'User1', 'user_2', 'User2', 'user_3', 'User3', 'user_4', 'User4', 'user_5', 'User5',
                ],
                ['uid', 'points_*', null, ['user_*'], null, false],
                ['User5', 'User2', 'User3', 'User4', 'User1'],
            ],
        ];
    }

    public function unexpectedValuesProvider(): array
    {
        return [
            'wrong GET argument type' => [
                ['key', null, null, 'wrong', null, false],
                'Wrong get argument type',
            ],
            'wrong sorting argument type' => [
                ['key', null, null, [], 'wrong', false],
                'Sorting argument accepts only: asc, desc values',
            ],
        ];
    }
}
