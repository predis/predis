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

namespace Predis\Command\Redis\CuckooFilter;

use Predis\Command\Redis\PredisCommandTestCase;
use Predis\Response\ServerException;

/**
 * @group commands
 * @group realm-stack
 */
class CFINSERTNX_Test extends PredisCommandTestCase
{
    /**
     * {@inheritDoc}
     */
    protected function getExpectedCommand(): string
    {
        return CFINSERTNX::class;
    }

    /**
     * {@inheritDoc}
     */
    protected function getExpectedId(): string
    {
        return 'CFINSERTNX';
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
     * @group connected
     * @group relay-resp3
     * @dataProvider filtersProvider
     * @param  array  $filterArguments
     * @param  string $key
     * @param  int    $expectedCapacity
     * @param  array  $expectedResponse
     * @return void
     * @requiresRedisBfVersion >= 1.0.0
     */
    public function testInsertItemsIntoNonExistingCuckooFilter(
        array $filterArguments,
        string $key,
        int $expectedCapacity,
        array $expectedResponse
    ): void {
        $redis = $this->getClient();

        $actualResponse = $redis->cfinsertnx(...$filterArguments);
        $info = $redis->cfinfo($key);

        $this->assertSame($expectedResponse, $actualResponse);
        $this->assertSame($expectedCapacity, $info['Size']);
    }

    /**
     * @group connected
     * @group relay-resp3
     * @return void
     * @requiresRedisBfVersion >= 1.0.0
     */
    public function testDoNotInsertAlreadyExistingItems(): void
    {
        $redis = $this->getClient();

        $redis->cfadd('filter', 'item1');
        $redis->cfadd('filter', 'item2');

        $actualResponse = $redis->cfinsertnx('filter', -1, false, 'item1', 'item2');
        $this->assertSame([0, 0], $actualResponse);
    }

    /**
     * @group connected
     * @return void
     * @requiresRedisBfVersion >= 1.0.0
     */
    public function testInsertThrowsErrorOnInsertingIntoNonExistingFilterWithNoCreateModifier(): void
    {
        $redis = $this->getClient();

        $this->expectException(ServerException::class);
        $this->expectExceptionMessage('ERR not found');

        $redis->cfinsertnx('key', -1, true, 'item');
    }

    /**
     * @group connected
     * @group relay-resp3
     * @return void
     * @requiresRedisBfVersion >= 1.0.0
     */
    public function testInsertIntoAlreadyExistingFilterWithNoCreateModifier(): void
    {
        $redis = $this->getClient();

        $redis->cfadd('filter', 'item');

        $actualResponse = $redis->cfinsertnx('filter', -1, true, 'item1');
        $this->assertSame([1], $actualResponse);
    }

    public function argumentsProvider(): array
    {
        return [
            'with default arguments' => [
                ['key', -1, false, 'item1'],
                ['key', 'ITEMS', 'item1'],
            ],
            'with CAPACITY modifier' => [
                ['key', 500, false, 'item1'],
                ['key', 'CAPACITY', 500, 'ITEMS', 'item1'],
            ],
            'with NOCREATE modifier' => [
                ['key', -1, true, 'item1'],
                ['key', 'NOCREATE', 'ITEMS', 'item1'],
            ],
            'with all arguments' => [
                ['key', 500, true, 'item1', 'item2'],
                ['key', 'CAPACITY', 500, 'NOCREATE', 'ITEMS', 'item1', 'item2'],
            ],
        ];
    }

    public function filtersProvider(): array
    {
        return [
            'with default arguments' => [
                ['key', -1, false, 'item1'],
                'key',
                1080,
                [1],
            ],
            'with modified CAPACITY' => [
                ['key', 500, false, 'item1'],
                'key',
                568,
                [1],
            ],
            'with multiple items' => [
                ['key', -1, false, 'item1', 'item2'],
                'key',
                1080,
                [1, 1],
            ],
        ];
    }
}
