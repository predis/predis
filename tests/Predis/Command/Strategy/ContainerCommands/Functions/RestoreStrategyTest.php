<?php

/*
 * This file is part of the Predis package.
 *
 * (c) 2009-2020 Daniele Alessandri
 * (c) 2021-2023 Till Krüss
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Command\Strategy\ContainerCommands\Functions;

use PredisTestCase;
use UnexpectedValueException;

class RestoreStrategyTest extends PredisTestCase
{
    /**
     * @var RestoreStrategy
     */
    private $strategy;

    protected function setUp(): void
    {
        $this->strategy = new RestoreStrategy();
    }

    /**
     * @dataProvider argumentsProvider
     * @param  array $actualArguments
     * @param  array $expectedArguments
     * @return void
     */
    public function testProcessArgumentsReturnsCorrectArguments(
        array $actualArguments,
        array $expectedArguments
    ): void {
        $this->assertSame($expectedArguments, $this->strategy->processArguments($actualArguments));
    }

    /**
     * @return void
     */
    public function testProcessArgumentsThrowsErrorOnWrongModifierValueGiven(): void
    {
        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('Modifier argument accepts only: FLUSH, APPEND, REPLACE values');

        $this->strategy->processArguments(['arg1', 'arg2', 'wrong']);
    }

    public function argumentsProvider(): array
    {
        return [
            'with FLUSH modifier' => [
                ['arg1', 'arg2', 'FLUSH'],
                ['arg1', 'arg2', 'FLUSH'],
            ],
            'with APPEND modifier' => [
                ['arg1', 'arg2', 'APPEND'],
                ['arg1', 'arg2', 'APPEND'],
            ],
            'with REPLACE modifier' => [
                ['arg1', 'arg2', 'REPLACE'],
                ['arg1', 'arg2', 'REPLACE'],
            ],
        ];
    }
}
