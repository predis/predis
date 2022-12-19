<?php

namespace Predis\Command\Redis\Json;

use Predis\Command\Redis\PredisCommandTestCase;
use UnexpectedValueException;

class JSONSET_Test extends PredisCommandTestCase
{
    /**
     * @inheritDoc
     */
    protected function getExpectedCommand(): string
    {
        return JSONSET::class;
    }

    /**
     * @inheritDoc
     */
    protected function getExpectedId(): string
    {
        return 'JSONSET';
    }

    /**
     * @group disconnected
     * @dataProvider argumentsProvider
     */
    public function testFilterArguments(array $actualArguments, array $expectedArguments): void
    {
        $command = $this->getCommand();
        $command->setArguments($actualArguments);

        $this->assertSame($expectedArguments, $command->getArguments());
    }

    /**
     * @group disconnected
     */
    public function testParseResponse(): void
    {
        $this->assertSame(1, $this->getCommand()->parseResponse(1));
    }

    /**
     * @group connected
     * @dataProvider jsonProvider
     * @param string $key
     * @param string $defaultJson
     * @param string $appendedJson
     * @param string $path
     * @param string|null $subcommand
     * @param string|null $expectedResponse
     * @param string $expectedJson
     * @return void
     * @requiresRedisJsonVersion >= 1.0.0
     */
    public function testSetCorrectJsonValueAndReturnsCorrespondingResponse(
        string $key,
        string $defaultJson,
        string $appendedJson,
        string $path,
        ?string $subcommand,
        ?string $expectedResponse,
        string $expectedJson
    ): void {
        $redis = $this->getClient();

        $this->assertEquals('OK', $redis->jsonset($key, '$', $defaultJson));
        $this->assertEquals($expectedResponse, $redis->jsonset($key, $path, $appendedJson, $subcommand));
        $this->assertSame($expectedJson, $redis->jsonget($key));
    }

    /**
     * @group connected
     * @return void
     * @requiresRedisJsonVersion >= 1.0.0
     */
    public function testThrowsExceptionOnUnexpectedValueGiven(): void
    {
        $redis = $this->getClient();

        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('Subcommand argument accepts only: nx, xx values');

        $redis->jsonset('key', '$', 'value', 'wrong');
    }

    public function argumentsProvider(): array
    {
        return [
            'with default arguments' => [
                ['key', 'path', 'value'],
                ['key', 'path', 'value']
            ],
            'with NX subcommand' => [
                ['key', 'path', 'value', 'nx'],
                ['key', 'path', 'value', 'NX'],
            ],
            'with XX subcommand' => [
                ['key', 'path', 'value', 'xx'],
                ['key', 'path', 'value', 'XX'],
            ],
        ];
    }

    public function jsonProvider(): array
    {
        return [
            'override json' => [
                'key',
                '{"key1":"value1","key2":"value2"}',
                '{"key3":"value3"}',
                '$',
                null,
                'OK',
                '{"key3":"value3"}'
            ],
            'override certain key - without subcommands' => [
                'key',
                '{"key1":"value1","key2":"value2"}',
                '"value3"',
                '$.key2',
                null,
                'OK',
                '{"key1":"value1","key2":"value3"}'
            ],
            'append to json - without subcommands' => [
                'key',
                '{"key1":"value1","key2":"value2"}',
                '"value3"',
                '$.key3',
                null,
                'OK',
                '{"key1":"value1","key2":"value2","key3":"value3"}'
            ],
            'override certain key - with XX subcommand' => [
                'key',
                '{"key1":"value1","key2":"value2"}',
                '"value3"',
                '$.key2',
                'xx',
                'OK',
                '{"key1":"value1","key2":"value3"}'
            ],
            'append to json - with NX subcommand' => [
                'key',
                '{"key1":"value1","key2":"value2"}',
                '"value3"',
                '$.key3',
                'nx',
                'OK',
                '{"key1":"value1","key2":"value2","key3":"value3"}'
            ],
            'override failed with XX subcommand' => [
                'key',
                '{"key1":"value1","key2":"value2"}',
                '"value3"',
                '$.key3',
                'xx',
                null,
                '{"key1":"value1","key2":"value2"}'
            ],
            'append failed with NX subcommand' => [
                'key',
                '{"key1":"value1","key2":"value2"}',
                '"value2"',
                '$.key2',
                'nx',
                null,
                '{"key1":"value1","key2":"value2"}'
            ]
        ];
    }
}
