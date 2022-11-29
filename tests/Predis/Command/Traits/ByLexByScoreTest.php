<?php

namespace Predis\Command\Traits;

use PredisTestCase;
use Predis\Command\Command as RedisCommand;
use UnexpectedValueException;

class ByLexByScoreTest extends PredisTestCase
{
    private $testClass;

    protected function setUp(): void
    {
        parent::setUp();

        $this->testClass = new class extends RedisCommand {
            use ByLexByScore;

            public static $byLexByScoreArgumentPositionOffset = 0;

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
        $this->testClass::$byLexByScoreArgumentPositionOffset = $offset;

        $this->testClass->setArguments($actualArguments);

        $this->assertSame($expectedArguments, $this->testClass->getArguments());
    }

    /**
     * @dataProvider unexpectedValuesProvider
     * @param array $actualArguments
     * @return void
     */
    public function testThrowsExceptionOnUnexpectedValue(array $actualArguments): void
    {
        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage("By argument accepts only \"bylex\" and \"byscore\" values");

        $this->testClass->setArguments($actualArguments);
    }

    public function argumentsProvider(): array
    {
        return [
            'by false argument' => [
                0,
                [false, 'second argument', 'third argument'],
                [false, 'second argument', 'third argument']
            ],
            'by argument first and there is arguments after' => [
                0,
                ['bylex', 'second argument', 'third argument'],
                ['BYLEX', 'second argument', 'third argument']
            ],
            'by argument last and there is arguments before' => [
                2,
                ['first argument', 'second argument', 'byscore'],
                ['first argument', 'second argument', 'BYSCORE']
            ],
            'by argument not the first and not the last' => [
                1,
                ['first argument', 'byscore', 'third argument'],
                ['first argument', 'BYSCORE', 'third argument']
            ],
        ];
    }

    public function unexpectedValuesProvider(): array
    {
        return [
            'true argument' => [[true]],
            'string argument, not BYLEX/BYSCORE' => [['wrong argument']]
        ];
    }
}
