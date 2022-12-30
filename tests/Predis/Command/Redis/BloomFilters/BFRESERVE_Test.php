<?php

namespace Predis\Command\Redis\BloomFilters;

use Predis\Command\Redis\PredisCommandTestCase;
use Predis\Response\ServerException;
use UnexpectedValueException;

class BFRESERVE_Test extends PredisCommandTestCase
{
    /**
     * @inheritDoc
     */
    protected function getExpectedCommand(): string
    {
        return BFRESERVE::class;
    }

    /**
     * @inheritDoc
     */
    protected function getExpectedId(): string
    {
        return 'BFRESERVE';
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
     * @dataProvider filtersProvider
     * @param array $filter
     * @param string $key
     * @param string $modifier
     * @param array $expectedModification
     * @return void
     * @requiresRedisBfVersion >= 1.0.0
     */
    public function testReserveCreatesBloomFilterWithCorrectConfiguration(
        array $filter,
        string $key,
        string $modifier,
        array $expectedModification
    ): void {
        $redis = $this->getClient();

        $actualResponse = $redis->bfreserve(...$filter);
        $this->assertEquals('OK', $actualResponse);
        $this->assertSame($expectedModification, $redis->bfinfo($key, $modifier));
    }

    /**
     * @group connected
     * @return void
     * @requiresRedisBfVersion 1.0.0
     */
    public function testThrowsExceptionOnUnexpectedValueGiven(): void
    {
        $redis = $this->getClient();

        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('Wrong expansion argument value or position offset');

        $redis->bfreserve('key', 0.01, 2, 0);
    }

    /**
     * @group connected
     * @requiresRedisBfVersion >= 1.0
     */
    public function testThrowsExceptionOnWrongType(): void
    {
        $this->expectException(ServerException::class);
        $this->expectExceptionMessage('Operation against a key holding the wrong kind of value');

        $redis = $this->getClient();

        $redis->set('bfreserve_foo', 'bar');
        $redis->bfreserve('bfreserve_foo', 0.01, 2);
    }

    public function argumentsProvider(): array
    {
        return [
            'with default arguments' => [
                ['key', 0.01, 2],
                ['key', 0.01, 2]
            ],
            'with EXPANSION argument' => [
                ['key', 0.01, 2, 2],
                ['key', 0.01, 2, 'EXPANSION', 2]
            ],
            'with NONSCALING modifier' => [
                ['key', 0.01, 2, -1, true],
                ['key', 0.01, 2, 'NONSCALING']
            ],
            'with all arguments' => [
                ['key', 0.01, 2, 2, true],
                ['key', 0.01, 2, 'EXPANSION', 2, 'NONSCALING']
            ]
        ];
    }

    public function filtersProvider(): array
    {
        return [
            'with default arguments' => [
                ['key', 0.01, 100],
                'key',
                'capacity',
                [100]
            ],
            'with modified expansion' => [
                ['key', 0.01, 100, 2],
                'key',
                'expansion',
                [2]
            ],
            'with NONSCALING modifier' => [
                ['key', 0.01, 100, -1, true],
                'key',
                'expansion',
                [null]
            ],
        ];
    }
}
