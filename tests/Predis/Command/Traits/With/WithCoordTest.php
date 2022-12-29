<?php

namespace Predis\Command\Traits\With;

use Predis\Command\Command as RedisCommand;
use PredisTestCase;
use UnexpectedValueException;

class WithCoordTest extends PredisTestCase
{
    private $testClass;

    protected function setUp(): void
    {
        parent::setUp();

        $this->testClass = new class extends RedisCommand {
            use WithCoord;

            public static $withCoordArgumentPositionOffset = 0;

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
        $this->testClass::$withCoordArgumentPositionOffset = $offset;

        $this->testClass->setArguments($actualArguments);

        $this->assertSame($expectedArguments, $this->testClass->getArguments());
    }

    /**
     * @return void
     */
    public function testThrowsExceptionOnUnexpectedValue(): void
    {
        $this->testClass::$withCoordArgumentPositionOffset = 0;

        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage("Wrong WITHCOORD argument type");

        $this->testClass->setArguments(['test']);
    }

    public function argumentsProvider(): array
    {
        return [
            'WITHCOORD false argument' => [
                0,
                [false, 'second argument', 'third argument'],
                [false, 'second argument', 'third argument']
            ],
            'WITHCOORD argument first and there is arguments after' => [
                0,
                [true, 'second argument', 'third argument'],
                ['WITHCOORD', 'second argument', 'third argument']
            ],
            'WITHCOORD argument last and there is arguments before' => [
                2,
                ['first argument', 'second argument', true],
                ['first argument', 'second argument', 'WITHCOORD']
            ],
            'WITHCOORD argument not the first and not the last' => [
                1,
                ['first argument', true, 'third argument'],
                ['first argument', 'WITHCOORD', 'third argument']
            ],
        ];
    }
}
