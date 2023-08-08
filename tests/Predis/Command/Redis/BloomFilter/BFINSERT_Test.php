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

namespace Predis\Command\Redis\BloomFilter;

use Predis\Command\Redis\PredisCommandTestCase;
use Predis\Response\ServerException;
use UnexpectedValueException;

/**
 * @group commands
 * @group realm-stack
 */
class BFINSERT_Test extends PredisCommandTestCase
{
    /**
     * {@inheritDoc}
     */
    protected function getExpectedCommand(): string
    {
        return BFINSERT::class;
    }

    /**
     * {@inheritDoc}
     */
    protected function getExpectedId(): string
    {
        return 'BFINSERT';
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
     * @group relay-incompatible
     * @dataProvider filtersProvider
     * @param  array  $arguments
     * @param  string $key
     * @param  string $modifier
     * @param  array  $expectedInfo
     * @param  array  $expectedResponse
     * @return void
     * @requiresRedisBfVersion >= 1.0.0
     */
    public function testInsertCreatesBloomFilterWithGivenItems(
        array $arguments,
        string $key,
        string $modifier,
        array $expectedInfo,
        array $expectedResponse
    ): void {
        $redis = $this->getClient();

        $actualResponse = $redis->bfinsert(...$arguments);

        $this->assertSame($expectedResponse, $actualResponse);
        $this->assertSame($expectedInfo, $redis->bfinfo($key, $modifier));
    }

    /**
     * @group connected
     * @return void
     * @requiresRedisBfVersion >= 2.6.0
     */
    public function testInsertCreatesBloomFilterWithGivenItemsResp3(): void
    {
        $redis = $this->getResp3Client();

        $actualResponse = $redis->bfinsert(
            'key',
            -1,
            -1,
            -1,
            false,
            false,
            'item1',
            'item2'
        );

        $this->assertSame([true, true], $actualResponse);
        $this->assertSame([
            'Capacity' => 100,
            'Size' => 240,
            'Number of filters' => 1,
            'Number of items inserted' => 2,
            'Expansion rate' => 2,
        ], $redis->bfinfo('key'));
    }

    /**
     * @group connected
     * @group relay-incompatible
     * @return void
     * @requiresRedisBfVersion >= 1.0.0
     */
    public function testInsertThrowsExceptionOnNonExistingBloomFilterWithNoCreateModifier(): void
    {
        $redis = $this->getClient();

        $this->expectException(ServerException::class);
        $this->expectExceptionMessage('ERR not found');

        $redis->bfinsert('key', -1, -1, -1, true, false, 'item1');
    }

    /**
     * @group connected
     * @group relay-incompatible
     * @return void
     * @requiresRedisBfVersion >= 1.0.0
     */
    public function testInsertAddItemOnlyOnExistingFilterWithNoCreateModifier(): void
    {
        $redis = $this->getClient();

        $redis->bfadd('key', 'item1');
        $actualResponse = $redis->bfinsert(
            'key',
            -1,
            -1,
            -1,
            false,
            false,
            'item2'
        );

        $this->assertSame([1], $actualResponse);
        $this->assertSame(
            [
                'Capacity' => 100,
                'Size' => 240,
                'Number of filters' => 1,
                'Number of items inserted' => 2,
                'Expansion rate' => 2,
            ],
            $redis->bfinfo('key', '')
        );
    }

    /**
     * @group connected
     * @group relay-incompatible
     * @dataProvider unexpectedValuesProvider
     * @param  array  $arguments
     * @param  string $expectedExceptionMessage
     * @return void
     * @requiresRedisBfVersion >= 1.0.0
     */
    public function testThrowsExceptionOnUnexpectedValueGiven(
        array $arguments,
        string $expectedExceptionMessage
    ): void {
        $redis = $this->getClient();

        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage($expectedExceptionMessage);

        $redis->bfinsert(...$arguments);
    }

    public function argumentsProvider(): array
    {
        return [
            'with default arguments' => [
                ['key', -1, -1, -1, false, false, 'item1'],
                ['key', 'ITEMS', 'item1'],
            ],
            'with CAPACITY modifier' => [
                ['key', 100, -1, -1, false, false, 'item1'],
                ['key', 'CAPACITY', 100, 'ITEMS', 'item1'],
            ],
            'with ERROR modifier' => [
                ['key', -1, 0.01, -1, false, false, 'item1'],
                ['key', 'ERROR', 0.01, 'ITEMS', 'item1'],
            ],
            'with EXPANSION modifier' => [
                ['key', -1, -1, 2, false, false, 'item1'],
                ['key', 'EXPANSION', 2, 'ITEMS', 'item1'],
            ],
            'with NOCREATE modifier' => [
                ['key', -1, -1, -1, true, false, 'item1'],
                ['key', 'NOCREATE', 'ITEMS', 'item1'],
            ],
            'with NONSCALING modifier' => [
                ['key', -1, -1, -1, false, true, 'item1'],
                ['key', 'NONSCALING', 'ITEMS', 'item1'],
            ],
            'with all arguments' => [
                ['key', 100, 0.01, 2, true, true, 'item1', 'item2'],
                ['key', 'CAPACITY', 100, 'ERROR', 0.01, 'EXPANSION', 2, 'NOCREATE', 'NONSCALING', 'ITEMS', 'item1', 'item2'],
            ],
        ];
    }

    public function filtersProvider(): array
    {
        return [
            'with default filter' => [
                ['key', -1, -1, -1, false, false, 'item1', 'item2'],
                'key',
                '',
                [
                    'Capacity' => 100,
                    'Size' => 240,
                    'Number of filters' => 1,
                    'Number of items inserted' => 2,
                    'Expansion rate' => 2,
                ],
                [1, 1],
            ],
            'with CAPACITY modifier' => [
                ['key', 120, -1, -1, false, false, 'item1', 'item2'],
                'key',
                'capacity',
                [120],
                [1, 1],
            ],
            'with ERROR modifier' => [
                ['key', -1, 0.01, -1, false, false, 'item1', 'item2'],
                'key',
                '',
                [
                    'Capacity' => 100,
                    'Size' => 240,
                    'Number of filters' => 1,
                    'Number of items inserted' => 2,
                    'Expansion rate' => 2,
                ],
                [1, 1],
            ],
            'with EXPANSION modifier' => [
                ['key', -1, -1, 3, false, false, 'item1', 'item2'],
                'key',
                'expansion',
                [3],
                [1, 1],
            ],
            'with NONSCALING modifier' => [
                ['key', -1, -1, -1, false, true, 'item1', 'item2'],
                'key',
                'expansion',
                [null],
                [1, 1],
            ],
            'with all arguments' => [
                ['key', 120, 0.01, 3, false, false, 'item1', 'item2'],
                'key',
                '',
                [
                    'Capacity' => 120,
                    'Size' => 264,
                    'Number of filters' => 1,
                    'Number of items inserted' => 2,
                    'Expansion rate' => 3,
                ],
                [1, 1],
            ],
        ];
    }

    public function unexpectedValuesProvider(): array
    {
        return [
            'with wrong CAPACITY' => [
                ['key', -5, -1, -1, false, false, 'item1', 'item2'],
                'Wrong capacity argument value or position offset',
            ],
            'with wrong ERROR' => [
                ['key', -1, -5, -1, false, false, 'item1', 'item2'],
                'Wrong error argument value or position offset',
            ],
            'with wrong EXPANSION' => [
                ['key', -1, -1, -5, false, false, 'item1', 'item2'],
                'Wrong expansion argument value or position offset',
            ],
        ];
    }
}
