<?php

namespace Predis\Command\Traits\BloomFilters;

use PredisTestCase;
use Predis\Command\Command as RedisCommand;

class ItemsTest extends PredisTestCase
{
    private $testClass;

    protected function setUp(): void
    {
        parent::setUp();

        $this->testClass = new class extends RedisCommand {
            use Items;

            public static $itemsArgumentPositionOffset = 0;

            public function getId()
            {
                return 'test';
            }
        };
    }

    /**
     * @dataProvider argumentsProvider
     * @param int $offset
     * @param array $arguments
     * @param array $expectedResponse
     * @return void
     */
    public function testReturnsCorrectArguments(int $offset, array $arguments, array $expectedResponse): void
    {
        $this->testClass::$itemsArgumentPositionOffset = $offset;

        $this->testClass->setArguments($arguments);

        $this->assertSameValues($expectedResponse, $this->testClass->getArguments());
    }

    public function argumentsProvider(): array
    {
        return [
            'with wrong offset' => [
                1,
                [],
                []
            ],
            'with non-default argument' => [
                0,
                ['item1', 'item2'],
                ['ITEMS', 'item1', 'item2']
            ]
        ];
    }
}
