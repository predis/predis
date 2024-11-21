<?php

/*
 * This file is part of the Predis package.
 *
 * (c) 2009-2020 Daniele Alessandri
 * (c) 2021-2024 Till Krüss
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Command\Traits\Json;

use Predis\Command\Command as RedisCommand;
use PredisTestCase;
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
     * @param  int   $offset
     * @param  array $actualArguments
     * @param  array $expectedResponse
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
        $this->expectExceptionMessage('Indent argument value should be a string');

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
                [false],
            ],
            'with correct argument' => [
                0,
                ['\t'],
                ['INDENT', '\t'],
            ],
        ];
    }
}
