<?php

namespace Predis\Command\Traits\Json;

use PredisTestCase;
use Predis\Command\Command as RedisCommand;
use UnexpectedValueException;

class IndentTest extends PredisTestCase
{
    private $testClass;

    protected function setUp(): void
    {
        $this->testClass = new class extends RedisCommand {
            use Indent;

            public static $indentArgumentPositionOffset = 0;

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
        $this->testClass::$indentArgumentPositionOffset = $offset;

        $this->testClass->setArguments($actualArguments);

        $this->assertSame($expectedResponse, $this->testClass->getArguments());
    }

    /**
     * @return void
     */
    public function testThrowsExceptionOnUnexpectedValueGiven(): void
    {
        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage("Indent argument value should be a string");

        $this->testClass->setArguments([1]);
    }

    public function argumentsProvider(): array
    {
        return [
            'with wrong offset' => [
                2,
                ['argument1'],
                ['argument1'],
            ],
            'with default value' => [
                0,
                [''],
                [false]
            ],
            'with correct argument' => [
                0,
                ['\t'],
                ['INDENT', '\t'],
            ],
        ];
    }
}
