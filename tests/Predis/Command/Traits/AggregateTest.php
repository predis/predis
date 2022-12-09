<?php

namespace Predis\Command\Traits;

use Predis\Command\Command as RedisCommand;
use PredisTestCase;
use UnexpectedValueException;

class AggregateTest extends PredisTestCase
{
    private $testClass;

    protected function setUp(): void
    {
        $this->testClass = new class extends RedisCommand {
            use Aggregate;

            public static $aggregateArgumentPositionOffset = 0;

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
        $this->testClass::$aggregateArgumentPositionOffset = $offset;

        $this->testClass->setArguments($actualArguments);

        $this->assertSame($expectedArguments, $this->testClass->getArguments());
    }

    /**
     * @dataProvider unexpectedValuesProvider
     * @param array $actualArguments
     * @return void
     */
    public function testThrowsExceptionOnUnexpectedValueGiven(array $actualArguments): void
    {
        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('Aggregate argument accepts only: min, max, sum values');

        $this->testClass->setArguments($actualArguments);
    }

    public function argumentsProvider(): array
    {
        return [
            'with wrong offset' => [2, ['argument1'], ['argument1']],
            'aggregate argument first and there is arguments after' => [
                0,
                ['sum', 'second argument', 'third argument'],
                ['AGGREGATE', 'SUM', 'second argument', 'third argument']
            ],
            'aggregate argument last and there is arguments before' => [
                2,
                ['first argument', 'second argument', 'min'],
                ['first argument', 'second argument', 'AGGREGATE', 'MIN']
            ],
            'aggregate argument not the first and not the last' => [
                1,
                ['first argument', 'max', 'third argument'],
                ['first argument', 'AGGREGATE', 'MAX', 'third argument']
            ],
            'aggregate argument the only argument' => [
                0,
                ['sum'],
                ['AGGREGATE', 'SUM']
            ]
        ];
    }

    public function unexpectedValuesProvider(): array
    {
        return [
            'with non-string argument' => [[1]],
            'with non enum value' => [['wrong']],
        ];
    }
}
