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

class FlushStrategyTest extends PredisTestCase
{
    /**
     * @var FlushStrategy
     */
    private $strategy;

    protected function setUp(): void
    {
        $this->strategy = new FlushStrategy();
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
        $this->expectExceptionMessage('Modifier argument accepts only: ASYNC, SYNC values');

        $this->strategy->processArguments(['arg1', 'wrong']);
    }

    public function argumentsProvider(): array
    {
        return [
            'with ASYNC modifier' => [
                ['arg1', 'ASYNC'],
                ['arg1', 'ASYNC'],
            ],
            'with SYNC modifier' => [
                ['arg1', 'SYNC'],
                ['arg1', 'SYNC'],
            ],
        ];
    }
}
