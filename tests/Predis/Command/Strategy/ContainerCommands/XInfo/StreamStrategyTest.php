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

namespace Predis\Command\Strategy\ContainerCommands\XInfo;

use Predis\Command\Argument\Stream\XInfoStreamOptions;
use PredisTestCase;

class StreamStrategyTest extends PredisTestCase
{
    /**
     * @var StreamStrategy
     */
    private $strategy;

    protected function setUp(): void
    {
        $this->strategy = new StreamStrategy();
    }

    /**
     * @dataProvider argumentsProvider
     * @param  array $actualArguments
     * @param  array $expectedArguments
     * @return void
     */
    public function testProcessArguments(
        array $actualArguments,
        array $expectedArguments
    ): void {
        $this->assertSame($expectedArguments, $this->strategy->processArguments($actualArguments));
    }

    /**
     * @group disconnected
     * @return void
     */
    public function testParseResponse(): void
    {
        $actualResponse = [['length', 1, 'entries-added', 1, 'entries', [['id', ['field', 'value']]]]];
        $expectedResponse = [['length' => 1, 'entries-added' => 1, 'entries' => [['id' => ['field' => 'value']]]]];

        $this->assertSame($expectedResponse, $this->strategy->parseResponse($actualResponse));
    }

    public function argumentsProvider(): array
    {
        return [
            'with default arguments' => [
                ['STREAM', 'key'],
                ['STREAM', 'key'],
            ],
            'with FULL modifier - no COUNT' => [
                ['STREAM', 'key', (new XInfoStreamOptions())->full()],
                ['STREAM', 'key', 'FULL'],
            ],
            'with FULL modifier - with COUNT' => [
                ['STREAM', 'key', (new XInfoStreamOptions())->full(15)],
                ['STREAM', 'key', 'FULL', 'COUNT', 15],
            ],
        ];
    }
}
