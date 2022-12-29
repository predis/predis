<?php

namespace Predis\Command\Redis\BloomFilters;

use Predis\Command\Redis\PredisCommandTestCase;
use Predis\Response\ServerException;
use UnexpectedValueException;

/**
 * @group commands
 * @group realm-bloom
 */
class BFINFO_Test extends PredisCommandTestCase
{
    /**
     * @inheritDoc
     */
    protected function getExpectedCommand(): string
    {
        return BFINFO::class;
    }

    /**
     * @inheritDoc
     */
    protected function getExpectedId(): string
    {
        return 'BFINFO';
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
     * @dataProvider responsesProvider
     */
    public function testParseResponse(array $actualResponse, array $expectedResponse): void
    {
        $this->assertSame($expectedResponse, $this->getCommand()->parseResponse($actualResponse));
    }

    /**
     * @group connected
     * @dataProvider filtersProvider
     * @param array $filter
     * @param string $key
     * @param string $modifier
     * @param array $expectedResponse
     * @return void
     * @requiresRedisBfVersion 1.0.0
     */
    public function testInfoReturnsCorrectInformationAboutBloomFilter(
        array $filter,
        string $key,
        string $modifier,
        array $expectedResponse
    ): void {
        $redis = $this->getClient();

        $redis->bfadd(...$filter);
        $this->assertSame($expectedResponse, $redis->bfinfo($key, $modifier));
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
        $this->expectExceptionMessage('Argument accepts only: capacity, size, filters, items, expansion values');

        $redis->bfinfo('key', 'wrong');
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

        $redis->set('binfo_foo', 'bar');
        $redis->bfinfo('binfo_foo');
    }

    public function argumentsProvider(): array
    {
        return [
            'without argument' => [
                [],
                []
            ],
            'with default modifier value' => [
                [''],
                []
            ],
            'with CAPACITY modifier' => [
                ['capacity'],
                ['CAPACITY'],
            ],
            'with SIZE modifier' => [
                ['size'],
                ['SIZE'],
            ],
            'with FILTERS modifier' => [
                ['filters'],
                ['FILTERS'],
            ],
            'with ITEMS modifier' => [
                ['items'],
                ['ITEMS'],
            ],
            'with EXPANSION modifier' => [
                ['expansion'],
                ['EXPANSION']
            ],
        ];
    }

    public function responsesProvider(): array
    {
        return [
            'with one modifier' => [
                [100],
                [100],
            ],
            'with all modifiers' => [
                [
                    'Capacity',
                    100,
                    'Size',
                    296,
                    'Number of filters',
                    1,
                    'Number of items inserted',
                    1,
                    'Expansion rate',
                    2
                ],
                [
                    'Capacity' => 100,
                    'Size' => 296,
                    'Number of filters' => 1,
                    'Number of items inserted' => 1,
                    'Expansion rate' => 2
                ]
            ]
        ];
    }

    public function filtersProvider(): array
    {
        return [
            'without modifier' => [
                ['key', 'item'],
                'key',
                '',
                [
                    'Capacity' => 100,
                    'Size' => 240,
                    'Number of filters' => 1,
                    'Number of items inserted' => 1,
                    'Expansion rate' => 2
                ]
            ],
            'with CAPACITY modifier' => [
                ['key', 'item'],
                'key',
                'capacity',
                [100],
            ],
            'with SIZE modifier' => [
                ['key', 'item'],
                'key',
                'size',
                [240],
            ],
            'with FILTERS modifier' => [
                ['key', 'item'],
                'key',
                'filters',
                [1],
            ],
            'with ITEMS modifier' => [
                ['key', 'item'],
                'key',
                'items',
                [1],
            ],
            'with EXPANSION modifier' => [
                ['key', 'item'],
                'key',
                'expansion',
                [2],
            ],
        ];
    }
}
