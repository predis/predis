<?php

namespace Predis\Command\Redis\AbstractCommand;

use Predis\Command\CommandInterface;
use PredisTestCase;

class BZPOPBaseTest extends PredisTestCase
{
    /**
     * @var CommandInterface
     */
    private $testCommand;

    protected function setUp(): void
    {
        parent::setUp();

        $this->testCommand = new class extends BZPOPBase {

            public function getId(): string
            {
                return 'test';
            }
        };
    }

    /**
     * @group disconnected
     * @dataProvider argumentsProvider
     */
    public function testFilterArguments(array $actualArguments, array $expectedArguments): void
    {
        $this->testCommand->setArguments($actualArguments);

        $this->assertSame($expectedArguments, $this->testCommand->getArguments());
    }

    /**
     * @group disconnected
     * @dataProvider responsesProvider
     */
    public function testParseResponse(array $actualResponse, array $expectedResponse): void
    {
        $this->assertSame($expectedResponse, $this->testCommand->parseResponse($actualResponse));
    }

    public function argumentsProvider(): array
    {
        return [
            'with one key' => [
                [['key1'], 1],
                ['key1', 1]
            ],
            'with multiple keys' => [
                [['key1', 'key2', 'key3'], 1],
                ['key1', 'key2', 'key3', 1]
            ],
        ];
    }

    public function responsesProvider(): array
    {
        return [
            'null-element array' => [
                [null],
                [null]
            ],
            'three-element array' => [
                ['key', 'member', 'score'],
                ['key' => ['member' => 'score']]
            ],
        ];
    }
}
