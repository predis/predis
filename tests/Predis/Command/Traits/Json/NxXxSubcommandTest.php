<?php

namespace Predis\Command\Traits\Json;

use PredisTestCase;
use Predis\Command\Command as RedisCommand;
use UnexpectedValueException;

class NxXxSubcommandTest extends PredisTestCase
{
    private $testClass;

    protected function setUp(): void
    {
        $this->testClass = new class extends RedisCommand {
            use NxXxArgument;

            public static $nxXxArgumentPositionOffset = 0;

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
     * @param array $expectedResponse
     * @return void
     */
    public function testReturnsCorrectArguments(
        int $offset,
        array $actualArguments,
        array $expectedResponse
    ): void {
        $this->testClass::$nxXxArgumentPositionOffset = $offset;

        $this->testClass->setArguments($actualArguments);

        $this->assertSame($expectedResponse, $this->testClass->getArguments());
    }

    /**
     * @return void
     */
    public function testThrowsExceptionOnUnexpectedValueGiven(): void
    {
        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage("Subcommand argument accepts only: nx, xx values");

        $this->testClass->setArguments(['wrong']);
    }

    public function argumentsProvider(): array
    {
        return [
            'with correct argument - NX' => [
                0,
                ['nx'],
                ['NX']
            ],
            'with correct argument - XX' => [
                0,
                ['xx'],
                ['XX']
            ],
            'with wrong offset' => [
                2,
                ['argument1'],
                ['argument1'],
            ],
            'with null argument' => [
                0,
                [null],
                [false]
            ],
        ];
    }
}
