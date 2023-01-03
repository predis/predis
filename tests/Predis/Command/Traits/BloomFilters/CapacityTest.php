<?php

namespace Predis\Command\Traits\BloomFilters;

use PredisTestCase;
use Predis\Command\Command as RedisCommand;
use UnexpectedValueException;

class CapacityTest extends PredisTestCase
{
    private $testClass;

    protected function setUp(): void
    {
        parent::setUp();

        $this->testClass = new class extends RedisCommand {
            use Capacity;

            public static $capacityArgumentPositionOffset = 0;

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
        $this->testClass::$capacityArgumentPositionOffset = $offset;

        $this->testClass->setArguments($arguments);

        $this->assertSameValues($expectedResponse, $this->testClass->getArguments());
    }

    /**
     * @return void
     */
    public function testThrowsErrorOnUnexpectedValueGiven(): void
    {
        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('Wrong capacity argument value or position offset');

        $this->testClass->setArguments([-5]);
    }

    public function argumentsProvider(): array
    {
        return [
            'with wrong offset' => [
                1,
                [],
                []
            ],
            'with default argument' => [
                0,
                [-1],
                [false]
            ],
            'with non-default argument' => [
                0,
                [10],
                ['CAPACITY', 10]
            ]
        ];
    }
}
