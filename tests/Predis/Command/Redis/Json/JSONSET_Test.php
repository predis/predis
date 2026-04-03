<?php

/*
 * This file is part of the Predis package.
 *
 * (c) 2009-2020 Daniele Alessandri
 * (c) 2021-2026 Till Krüss
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Command\Redis\Json;

use Predis\Command\PrefixableCommand;
use Predis\Command\Redis\PredisCommandTestCase;
use UnexpectedValueException;

/**
 * @group commands
 * @group realm-stack
 */
class JSONSET_Test extends PredisCommandTestCase
{
    /**
     * {@inheritDoc}
     */
    protected function getExpectedCommand(): string
    {
        return JSONSET::class;
    }

    /**
     * {@inheritDoc}
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
     * @group disconnected
     */
    public function testPrefixKeys(): void
    {
        /** @var PrefixableCommand $command */
        $command = $this->getCommand();
        $actualArguments = ['arg1'];
        $prefix = 'prefix:';
        $expectedArguments = ['prefix:arg1'];

        $command->setArguments($actualArguments);
        $command->prefixKeys($prefix);

        $this->assertSame($expectedArguments, $command->getArguments());
    }

    /**
     * @group connected
     * @group relay-resp3
     * @dataProvider jsonProvider
     * @param  string      $key
     * @param  string      $defaultJson
     * @param  string      $appendedJson
     * @param  string      $path
     * @param  string|null $nxXxArgument
     * @param  string|null $expectedResponse
     * @param  string      $expectedJson
     * @return void
     * @requiresRedisJsonVersion >= 1.0.0
     */
    public function testSetCorrectJsonValueAndReturnsCorrespondingResponse(
        string $key,
        string $defaultJson,
        string $appendedJson,
        string $path,
        ?string $nxXxArgument,
        ?string $expectedResponse,
        string $expectedJson
    ): void {
        $redis = $this->getClient();

        $this->assertEquals('OK', $redis->jsonset($key, '$', $defaultJson));
        $this->assertEquals($expectedResponse, $redis->jsonset($key, $path, $appendedJson, $nxXxArgument));
        $this->assertSame($expectedJson, $redis->jsonget($key));
    }

    /**
     * @group connected
     * @return void
     * @requiresRedisJsonVersion >= 2.6.1
     */
    public function testSetCorrectJsonValueAndReturnsCorrespondingResponseResp3(): void
    {
        $redis = $this->getResp3Client();

        $this->assertEquals('OK', $redis->jsonset('key', '$', '{"key1":"value1","key2":"value2"}'));
        $this->assertEquals('OK', $redis->jsonset('key', '$', '{"key3":"value3"}'));
        $this->assertSame('{"key3":"value3"}', $redis->jsonget('key'));
    }

    /**
     * @group connected
     * @group relay-resp3
     * @return void
     * @requiresRedisJsonVersion >= 1.0.0
     */
    public function testThrowsExceptionOnUnexpectedValueGiven(): void
    {
        $redis = $this->getClient();

        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('Argument accepts only: nx, xx values');

        $redis->jsonset('key', '$', 'value', 'wrong');
    }

    /**
     * @group disconnected
     */
    public function testThrowsExceptionOnUnexpectedFphaValueGiven(): void
    {
        $command = $this->getCommand();

        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('FPHA argument accepts only: bf16, fp16, fp32, fp64 values');

        $command->setArguments(['key', '$', 'value', null, 'wrong']);
    }

    /**
     * @group connected
     * @group relay-resp3
     * @dataProvider fphaProvider
     * @param  string $fpha
     * @return void
     * @requiresRedisVersion >= 8.7.0
     */
    public function testSetFloatArrayWithFphaTypeAndReturnsOk(string $fpha): void
    {
        $redis = $this->getClient();

        $this->assertEquals('OK', $redis->jsonset('key', '$', '[1.1, 2.2, 3.3]', null, $fpha));
        $storedValue = $redis->jsonget('key');
        $this->assertIsString($storedValue);
    }

    public function argumentsProvider(): array
    {
        return [
            'with default arguments' => [
                ['key', 'path', 'value'],
                ['key', 'path', 'value'],
            ],
            'with NX argument' => [
                ['key', 'path', 'value', 'nx'],
                ['key', 'path', 'value', 'NX'],
            ],
            'with XX argument' => [
                ['key', 'path', 'value', 'xx'],
                ['key', 'path', 'value', 'XX'],
            ],
            'with FPHA BF16 argument' => [
                ['key', 'path', 'value', null, 'bf16'],
                ['key', 'path', 'value', 'FPHA', 'BF16'],
            ],
            'with FPHA FP16 argument' => [
                ['key', 'path', 'value', null, 'fp16'],
                ['key', 'path', 'value', 'FPHA', 'FP16'],
            ],
            'with FPHA FP32 argument' => [
                ['key', 'path', 'value', null, 'fp32'],
                ['key', 'path', 'value', 'FPHA', 'FP32'],
            ],
            'with FPHA FP64 argument' => [
                ['key', 'path', 'value', null, 'fp64'],
                ['key', 'path', 'value', 'FPHA', 'FP64'],
            ],
            'with NX and FPHA FP32 arguments' => [
                ['key', 'path', 'value', 'nx', 'fp32'],
                ['key', 'path', 'value', 'NX', 'FPHA', 'FP32'],
            ],
        ];
    }

    public function fphaProvider(): array
    {
        return [
            'with BF16 type' => ['bf16'],
            'with FP16 type' => ['fp16'],
            'with FP32 type' => ['fp32'],
            'with FP64 type' => ['fp64'],
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
                '{"key3":"value3"}',
            ],
            'override certain key - without nxXx argument' => [
                'key',
                '{"key1":"value1","key2":"value2"}',
                '"value3"',
                '$.key2',
                null,
                'OK',
                '{"key1":"value1","key2":"value3"}',
            ],
            'append to json - without nxXx argument' => [
                'key',
                '{"key1":"value1","key2":"value2"}',
                '"value3"',
                '$.key3',
                null,
                'OK',
                '{"key1":"value1","key2":"value2","key3":"value3"}',
            ],
            'override certain key - with XX argument' => [
                'key',
                '{"key1":"value1","key2":"value2"}',
                '"value3"',
                '$.key2',
                'xx',
                'OK',
                '{"key1":"value1","key2":"value3"}',
            ],
            'append to json - with NX argument' => [
                'key',
                '{"key1":"value1","key2":"value2"}',
                '"value3"',
                '$.key3',
                'nx',
                'OK',
                '{"key1":"value1","key2":"value2","key3":"value3"}',
            ],
            'override failed with XX argument' => [
                'key',
                '{"key1":"value1","key2":"value2"}',
                '"value3"',
                '$.key3',
                'xx',
                null,
                '{"key1":"value1","key2":"value2"}',
            ],
            'append failed with NX argument' => [
                'key',
                '{"key1":"value1","key2":"value2"}',
                '"value2"',
                '$.key2',
                'nx',
                null,
                '{"key1":"value1","key2":"value2"}',
            ],
        ];
    }
}
