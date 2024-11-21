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

namespace Predis\Command\Traits;

use Predis\Command\Command as RedisCommand;
use PredisTestCase;
use UnexpectedValueException;

class MinMaxModifierTest extends PredisTestCase
{
    private $testClass;

    protected function setUp(): void
    {
        parent::setUp();

        $this->testClass = new class extends RedisCommand {
            use MinMaxModifier;

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
     * @param  array $expectedArguments
     * @return void
     */
    public function testResolveModifierModifyArrayCorrect(
        int $offset,
        array $actualArguments,
        array $expectedArguments
    ): void {
        $this->testClass->resolveModifier($offset, $actualArguments);
        $this->assertSame($expectedArguments, $actualArguments);
    }

    public function testThrowsExceptionOnWrongModifierValue(): void
    {
        $arguments = ['argument1', 'wrong modifier'];

        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('Wrong type of modifier given');

        $this->testClass->resolveModifier(1, $arguments);
    }

    public function argumentsProvider(): array
    {
        return [
            'with modifier' => [
                0,
                ['max'],
                ['MAX'],
            ],
            'without modifier' => [
                2,
                ['argument1', 'argument2'],
                ['argument1', 'argument2', 'MIN'],
            ],
        ];
    }
}
