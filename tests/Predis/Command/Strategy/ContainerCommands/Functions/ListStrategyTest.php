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

class ListStrategyTest extends PredisTestCase
{
    /**
     * @var ListStrategy
     */
    private $strategy;

    protected function setUp(): void
    {
        $this->strategy = new ListStrategy();
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

    public function argumentsProvider(): array
    {
        return [
            'with default arguments' => [
                ['arg1'],
                ['arg1'],
            ],
            'with LIBRARYNAME modifier' => [
                ['arg1', 'libName'],
                ['arg1', 'LIBRARYNAME', 'libName'],
            ],
            'with WITHCODE modifier' => [
                ['arg1', '', true],
                ['arg1', 'WITHCODE'],
            ],
            'with all arguments' => [
                ['arg1', 'libName', true],
                ['arg1', 'LIBRARYNAME', 'libName', 'WITHCODE'],
            ],
        ];
    }
}
