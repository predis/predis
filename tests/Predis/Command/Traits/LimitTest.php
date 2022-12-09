<?php

namespace Predis\Command\Traits;

use PredisTestCase;
use Predis\Command\Command as RedisCommand;
use UnexpectedValueException;

class LimitTest extends PredisTestCase
{
    private $testClass;

    protected function setUp(): void
    {
        parent::setUp();

        $this->testClass = new class extends RedisCommand {
            use Limit;

            public static $limitArgumentPositionOffset = 0;

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
        $this->testClass::$limitArgumentPositionOffset = $offset;

        $this->testClass->setArguments($actualArguments);

        $this->assertSame($expectedArguments, $this->testClass->getArguments());
    }

    /**
     * @return void
     */
    public function testThrowsExceptionOnUnexpectedValue(): void
    {
        $this->testClass::$limitArgumentPositionOffset = 0;

        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage("Wrong limit argument type");

        $this->testClass->setArguments(['test']);
    }

    public function argumentsProvider(): array
    {
        return [
            'limit false argument first and there is arguments after' => [
                0,
                [false, 'second argument', 'third argument'],
                []
            ],
            'limit false argument last and there is arguments before' => [
                2,
                ['first argument', 'second argument', false],
                ['first argument', 'second argument'],
            ],
            'limit false argument not the first and not the last' => [
                1,
                ['first argument', false, 'third argument'],
                ['first argument'],
            ],
            'limit argument first and there is arguments after' => [
                0,
                [true, 'second argument', 'third argument'],
                ['LIMIT', 'second argument', 'third argument']
            ],
            'limit argument last and there is arguments before' => [
                2,
                ['first argument', 'second argument', true],
                ['first argument', 'second argument', 'LIMIT']
            ],
            'limit argument not the first and not the last' => [
                1,
                ['first argument', true, 'third argument'],
                ['first argument', 'LIMIT', 'third argument']
            ],
            'limit argument is integer' => [
                0,
                [1],
                ['LIMIT', 1]
            ],
            'limit argument with wrong offset' => [
                2,
                [1],
                [1],
            ],
        ];
    }
}
