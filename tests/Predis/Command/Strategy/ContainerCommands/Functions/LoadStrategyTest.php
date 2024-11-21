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

namespace Predis\Command\Strategy\ContainerCommands\Functions;

use PredisTestCase;

class LoadStrategyTest extends PredisTestCase
{
    /**
     * @var LoadStrategy
     */
    private $strategy;

    protected function setUp(): void
    {
        $this->strategy = new LoadStrategy();
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
            'with less then or equal 2 arguments' => [
                ['arg1', 'arg2'],
                ['arg1', 'arg2'],
            ],
            'with last argument equals true' => [
                ['arg1', 'arg2', true],
                ['arg1', 'REPLACE', 'arg2'],
            ],
            'with last argument equals false' => [
                ['arg1', 'arg2', false],
                ['arg1', 'arg2'],
            ],
        ];
    }
}
