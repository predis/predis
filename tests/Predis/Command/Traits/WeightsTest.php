<?php

namespace Predis\Command\Traits;

use Predis\Command\Command as RedisCommand;
use PredisTestCase;
use UnexpectedValueException;

class WeightsTest extends PredisTestCase
{
    private $testClass;

    protected function setUp(): void
    {
        $this->testClass = new class extends RedisCommand {
            use Weights;

            public static $weightsArgumentPositionOffset = 0;

            public function getId()
            {
                return 'test';
            }
        };
    }

    /**
     * @dataProvider argumentsProvider
     * @param int $offset
     * @param array $actualArguments
     * @param array $expectedArguments
     * @return void
     */
    public function testReturnsCorrectArguments(int $offset, array $actualArguments, array $expectedArguments): void
    {
        $this->testClass::$weightsArgumentPositionOffset = $offset;

        $this->testClass->setArguments($actualArguments);

        $this->assertSame($expectedArguments, $this->testClass->getArguments());
    }

    /**
     * @return void
     */
    public function testThrowsExceptionOnUnexpectedValueGiven(): void
    {
        $actualArguments = [1];
        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('Wrong weights argument type');

        $this->testClass->setArguments($actualArguments);
    }


    public function argumentsProvider(): array
    {
        return [
            'with wrong offset' => [2, ['argument1'], ['argument1']],
            'weights argument first and there is arguments after' => [
                0,
                [[1, 2], 'second argument', 'third argument'],
                ['WEIGHTS', 1, 2, 'second argument', 'third argument']
            ],
            'weights argument last and there is arguments before' => [
                2,
                ['first argument', 'second argument', [1, 2]],
                ['first argument', 'second argument', 'WEIGHTS', 1, 2]
            ],
            'weights argument not the first and not the last' => [
                1,
                ['first argument', [1, 2], 'third argument'],
                ['first argument', 'WEIGHTS', 1, 2, 'third argument']
            ],
            'weights argument the only argument' => [
                0,
                [[1, 2]],
                ['WEIGHTS', 1, 2]
            ]
        ];
    }
}
