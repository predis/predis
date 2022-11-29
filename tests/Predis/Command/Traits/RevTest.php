<?php

namespace Predis\Command\Traits;

use Predis\Command\Command as RedisCommand;
use PredisTestCase;
use UnexpectedValueException;

class RevTest extends PredisTestCase
{
    private $testClass;

    protected function setUp(): void
    {
        parent::setUp();

        $this->testClass = new class extends RedisCommand {
            use Rev;

            public static $revArgumentPositionOffset = 0;

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
        $this->testClass::$revArgumentPositionOffset = $offset;

        $this->testClass->setArguments($actualArguments);

        $this->assertSame($expectedArguments, $this->testClass->getArguments());
    }

    /**
     * @return void
     */
    public function testThrowsExceptionOnUnexpectedValue(): void
    {
        $this->testClass::$revArgumentPositionOffset = 0;

        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage("Wrong rev argument type");

        $this->testClass->setArguments(['test']);
    }

    public function argumentsProvider(): array
    {
        return [
            'rev false argument' => [
                0,
                [false, 'second argument', 'third argument'],
                [false, 'second argument', 'third argument']
            ],
            'rev argument first and there is arguments after' => [
                0,
                [true, 'second argument', 'third argument'],
                ['REV', 'second argument', 'third argument']
            ],
            'rev argument last and there is arguments before' => [
                2,
                ['first argument', 'second argument', true],
                ['first argument', 'second argument', 'REV']
            ],
            'rev argument not the first and not the last' => [
                1,
                ['first argument', true, 'third argument'],
                ['first argument', 'REV', 'third argument']
            ],
        ];
    }
}
