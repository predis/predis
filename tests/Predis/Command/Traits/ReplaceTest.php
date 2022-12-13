<?php

namespace Predis\Command\Traits;

use Predis\Command\Command as RedisCommand;
use PredisTestCase;

class ReplaceTest extends PredisTestCase
{
    private $testClass;

    protected function setUp(): void
    {
        parent::setUp();

        $this->testClass = new class extends RedisCommand {
            use Replace;

            public function getId()
            {
                return 'test';
            }
        };
    }

    /**
     * @dataProvider argumentsProvider
     * @param array $arguments
     * @param array $expectedResponse
     * @return void
     */
    public function testReturnsCorrectArguments(array $arguments, array $expectedResponse): void
    {
        $this->testClass->setArguments($arguments);

        $this->assertSame($expectedResponse, $this->testClass->getArguments());
    }

    public function argumentsProvider(): array
    {
        return [
            'with boolean - true' => [[true],['REPLACE']],
            'with boolean - false' => [[false], []],
            'with non boolean' => [['string'], ['string']],
        ];
    }
}
