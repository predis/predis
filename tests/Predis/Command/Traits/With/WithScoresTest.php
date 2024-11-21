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

namespace Predis\Command\Traits\With;

use Predis\Command\Command as RedisCommand;
use PredisTestCase;

class WithScoresTest extends PredisTestCase
{
    /**
     * @var RedisCommand
     */
    private $testClass;

    protected function setUp(): void
    {
        parent::setUp();

        $this->testClass = new class extends RedisCommand {
            use WithScores;

            public function getId()
            {
                return 'test';
            }
        };
    }

    /**
     * @dataProvider valuesProvider
     * @param  array $actualArguments
     * @param  array $expectedArguments
     * @return void
     */
    public function testReturnsCorrectArguments(array $actualArguments, array $expectedArguments): void
    {
        $this->testClass->setArguments($actualArguments);

        $this->assertSame($expectedArguments, $this->testClass->getArguments());
    }

    /**
     * @dataProvider dataProvider
     * @param  array $actualData
     * @param  array $expectedResponse
     * @return void
     */
    public function testParseDataReturnsCorrectResponse(array $actualData, array $expectedResponse): void
    {
        $this->testClass->setArguments($actualData);

        $arguments = $this->testClass->getArguments();

        $this->assertSame($expectedResponse, $this->testClass->parseResponse($arguments));
    }

    public function valuesProvider(): array
    {
        return [
            'with last argument boolean - true' => [['test', 'test1', true], ['test', 'test1', 'WITHSCORES']],
            'with last argument boolean - false' => [['test', 'test1', false], ['test', 'test1']],
            'with last argument non boolean' => [['test', 'test1', 1], ['test', 'test1', 1]],
        ];
    }

    public function dataProvider(): array
    {
        return [
            'with empty arguments' => [[], [null]],
            'without modifier' => [['member1', '1', 'member2', '2'], ['member1', '1', 'member2', '2']],
            'with wrong modifier' => [
                ['member1', '1', 'member2', '2', 'WITHSCOREE'],
                ['member1', '1', 'member2', '2', 'WITHSCOREE'],
            ],
            'with modifier' => [
                ['member1', '1', 'member2', '2', 'WITHSCORES'],
                ['member1' => '1', 'member2' => '2'],
            ],
        ];
    }
}
