<?php

namespace Predis\Command\Redis\BloomFilters;

use Predis\Command\Redis\PredisCommandTestCase;
use Predis\Response\ServerException;

class BFCARD_Test extends PredisCommandTestCase
{
    /**
     * @inheritDoc
     */
    protected function getExpectedCommand(): string
    {
        return BFCARD::class;
    }

    /**
     * @inheritDoc
     */
    protected function getExpectedId(): string
    {
        return 'BFCARD';
    }

    /**
     * @group disconnected
     */
    public function testFilterArguments(): void
    {
        $actualArguments = ['key'];
        $expectedArguments = ['key'];

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
     * @dataProvider filtersProvider
     * @param array $filter
     * @param string $key
     * @param int $expectedResponse
     * @return void
     * @requiresRedisBfVersion >= 1.0.0
     */
    public function testCardReturnsCardinalityOfGivenBloomFilter(
        array $filter,
        string $key,
        int $expectedResponse
    ): void {
        $redis = $this->getClient();

        $redis->bfmadd(...$filter);

        $this->assertSame($expectedResponse, $redis->bfcard($key));
    }

    /**
     * @group connected
     * @requiresRedisBfVersion >= 1.0.0
     */
    public function testThrowsExceptionOnWrongType(): void
    {
        $this->expectException(ServerException::class);
        $this->expectExceptionMessage('Operation against a key holding the wrong kind of value');

        $redis = $this->getClient();

        $redis->set('bfcard_foo', 'bar');
        $redis->bfcard('bfcard_foo');
    }

    public function filtersProvider(): array
    {
        return [
            'with one item in filter' => [
                ['key', 'item1'],
                'key',
                1
            ],
            'with multiple items in filter' => [
                ['key', 'item1', 'item2', 'item3'],
                'key',
                3
            ],
            'with non-existing key' => [
                ['key', 'item1'],
                'key1',
                0
            ]
        ];
    }
}
