<?php

namespace Predis\Command\Traits;

use PredisTestCase;
use Predis\Command\Command as RedisCommand;

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
     * @param array $actualArguments
     * @param array $expectedArguments
     * @return void
     */
    public function testReturnsCorrectArguments(array $actualArguments, array $expectedArguments): void
    {
        $this->testClass->setArguments($actualArguments);

        $this->assertSame($expectedArguments, $this->testClass->getArguments());
    }

    /**
     * @dataProvider dataProvider
     * @param array $actualData
     * @param array $expectedResponse
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
            ]
        ];
    }
}
