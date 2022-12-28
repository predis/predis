<?php

namespace Predis\Command\Traits;

use PredisTestCase;
use Predis\Command\Command as RedisCommand;
use UnexpectedValueException;

class TimeoutTest extends PredisTestCase
{
    private $testClass;

    protected function setUp(): void
    {
        parent::setUp();

        $this->testClass = new class extends RedisCommand {
            use Timeout;

            public static $timeoutArgumentPositionOffset = 0;

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
        $this->testClass::$timeoutArgumentPositionOffset = $offset;

        $this->testClass->setArguments($arguments);

        $this->assertSameValues($expectedResponse, $this->testClass->getArguments());
    }

    /**
     * @return void
     */
    public function testThrowsExceptionOnUnexpectedValueGiven(): void
    {
        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('Wrong timeout argument value or position offset');

        $this->testClass->setArguments([0]);
    }

    public function argumentsProvider(): array
    {
        return [
            'with TIMEOUT argument' => [
                0,
                [10],
                ['TIMEOUT', 10]
            ],
            'with wrong offset given' => [
                1,
                [],
                []
            ],
            'with default value' => [
                0,
                [-1],
                [false]
            ]
        ];
    }
}
