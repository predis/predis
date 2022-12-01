<?php

namespace Predis\Command\Traits;

use PredisTestCase;
use Predis\Command\Command as RedisCommand;
use UnexpectedValueException;

class CountTest extends PredisTestCase
{
    private $testClass;

    protected function setUp(): void
    {
        parent::setUp();

        $this->testClass = new class extends RedisCommand {
            use Count;

            public static $countArgumentPositionOffset = 2;

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
        $this->testClass::$countArgumentPositionOffset = $offset;

        $this->testClass->setArguments($arguments);

        $this->assertSameValues($expectedResponse, $this->testClass->getArguments());
    }

    public function testThrowsErrorOnWrongCountValue(): void
    {
        $this->testClass::$countArgumentPositionOffset = 0;

        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('Wrong count argument value or position offset');

        $this->testClass->setArguments([0]);
    }

    public function argumentsProvider(): array
    {
        return [
            'with count argument' => [
                0,
                [2],
                ['COUNT', 2]
            ],
            'without count argument' => [
                2,
                ['argument1', 'argument2'],
                ['argument1', 'argument2']
            ]
        ];
    }
}
