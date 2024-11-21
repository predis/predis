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
     * @group disconnected
     * @param  array $actualArguments
     * @param  array $expectedResponse
     * @return void
     */
    public function testProcessArguments(array $actualArguments, array $expectedResponse): void
    {
        $this->assertSame($expectedResponse, $this->strategy->processArguments($actualArguments));
    }

    public function argumentsProvider(): array
    {
        return [
            'with default arguments' => [
                ['LIST', null, false],
                ['LIST'],
            ],
            'with LIBRARYNAME modifier' => [
                ['LIST', 'libraryname', false],
                ['LIST', 'LIBRARYNAME', 'libraryname'],
            ],
            'with WITHCODE modifier' => [
                ['LIST', null, true],
                ['LIST', 'WITHCODE'],
            ],
            'with all arguments' => [
                ['LIST', 'libraryname', true],
                ['LIST', 'LIBRARYNAME', 'libraryname', 'WITHCODE'],
            ],
        ];
    }
}
