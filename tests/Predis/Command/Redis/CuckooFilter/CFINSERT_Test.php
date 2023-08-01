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

namespace Predis\Command\Redis\CuckooFilter;

use Predis\Command\Redis\PredisCommandTestCase;
use Predis\Response\ServerException;
use UnexpectedValueException;

/**
 * @group commands
 * @group realm-stack
 */
class CFINSERT_Test extends PredisCommandTestCase
{
    /**
     * {@inheritDoc}
     */
    protected function getExpectedCommand(): string
    {
        return CFINSERT::class;
    }

    /**
     * {@inheritDoc}
     */
    protected function getExpectedId(): string
    {
        return 'CFINSERT';
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
     * @group relay-incompatible
     * @dataProvider filtersProvider
     * @param  array  $filterArguments
     * @param  string $key
     * @param  int    $expectedCapacity
     * @param  array  $expectedResponse
     * @return void
     * @requiresRedisBfVersion >= 1.0.0
     */
    public function testInsertItemsIntoGivenCuckooFilter(
        array $filterArguments,
        string $key,
        int $expectedCapacity,
        array $expectedResponse
    ): void {
        $redis = $this->getClient();

        $actualResponse = $redis->cfinsert(...$filterArguments);
        $info = $redis->cfinfo($key);

        $this->assertSame($expectedResponse, $actualResponse);
        $this->assertSame($expectedCapacity, $info['Size']);
    }

    /**
     * @group connected
     * @group relay-incompatible
     * @return void
     * @requiresRedisBfVersion >= 1.0.0
     */
    public function testInsertIgnoresCapacityModifierOnAlreadyExistingFilter(): void
    {
        $redis = $this->getClient();

        $redis->cfadd('filter', 'item');

        $actualResponse = $redis->cfinsert('filter', 500, false, 'item1');
        $info = $redis->cfinfo('filter');

        $this->assertSame([1], $actualResponse);
        $this->assertSame(1080, $info['Size']);
    }

    /**
     * @group connected
     * @return void
     * @requiresRedisBfVersion >= 2.6.0
     */
    public function testInsertIgnoresCapacityModifierOnAlreadyExistingFilterResp3(): void
    {
        $redis = $this->getResp3Client();

        $redis->cfadd('filter', 'item');

        $actualResponse = $redis->cfinsert('filter', 500, false, 'item1');
        $info = $redis->cfinfo('filter');

        $this->assertSame([true], $actualResponse);
        $this->assertSame(1080, $info['Size']);
    }

    /**
     * @group connected
     * @group relay-incompatible
     * @return void
     * @requiresRedisBfVersion >= 1.0.0
     */
    public function testInsertThrowsErrorOnInsertingIntoNonExistingFilterWithNoCreateModifier(): void
    {
        $redis = $this->getClient();

        $this->expectException(ServerException::class);
        $this->expectExceptionMessage('ERR not found');

        $redis->cfinsert('key', -1, true, 'item');
    }

    /**
     * @group connected
     * @group relay-incompatible
     * @return void
     * @requiresRedisBfVersion >= 1.0.0
     */
    public function testInsertIntoAlreadyExistingFilterWithNoCreateModifier(): void
    {
        $redis = $this->getClient();

        $redis->cfadd('filter', 'item');

        $actualResponse = $redis->cfinsert('filter', -1, true, 'item1');
        $this->assertSame([1], $actualResponse);
    }

    /**
     * @group connected
     * @group relay-incompatible
     * @return void
     * @requiresRedisBfVersion >= 1.0.0
     */
    public function testThrowsExceptionOnUnexpectedValueGiven(): void
    {
        $redis = $this->getClient();

        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('Wrong NOCREATE argument type');

        $redis->cfinsert('key', -1, 'wrong', 'item');
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
