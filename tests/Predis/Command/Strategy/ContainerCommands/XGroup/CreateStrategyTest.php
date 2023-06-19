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

namespace Predis\Command\Strategy\ContainerCommands\XGroup;

use PredisTestCase;

class CreateStrategyTest extends PredisTestCase
{
    /**
     * @var CreateStrategy
     */
    private $strategy;

    protected function setUp(): void
    {
        $this->strategy = new CreateStrategy();
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
                ['CREATE', 'key', 'group', '$'],
                ['CREATE', 'key', 'group', '$'],
            ],
            'with MKSTREAM modifier' => [
                ['CREATE', 'key', 'group', '$', true],
                ['CREATE', 'key', 'group', '$', 'MKSTREAM'],
            ],
            'with ENTRIESREAD modifier' => [
                ['CREATE', 'key', 'group', '$', false, 'entry'],
                ['CREATE', 'key', 'group', '$', 'ENTRIESREAD', 'entry'],
            ],
            'with all arguments modifier' => [
                ['CREATE', 'key', 'group', '$', true, 'entry'],
                ['CREATE', 'key', 'group', '$', 'MKSTREAM', 'ENTRIESREAD', 'entry'],
            ],
        ];
    }
}
