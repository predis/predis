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

namespace Predis\Command\Redis\Json;

use Predis\Command\Redis\PredisCommandTestCase;
use UnexpectedValueException;

/**
 * @group commands
 * @group realm-stack
 */
class JSONGET_Test extends PredisCommandTestCase
{
    /**
     * {@inheritDoc}
     */
    protected function getExpectedCommand(): string
    {
        return JSONGET::class;
    }

    /**
     * {@inheritDoc}
     */
    protected function getExpectedId(): string
    {
        return 'JSONGET';
    }

    /**
     * @group disconnected
     * @dataProvider argumentsProvider
     */
    public function testFilterArguments(array $actualArguments, array $expectedArguments): void
    {
        $command = $this->getCommand();
        $command->setArguments($actualArguments);

        $this->assertSameValues($expectedArguments, $command->getArguments());
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
     * @param  array  $jsonData
     * @param  string $key
     * @param  string $indent
     * @param  string $newline
     * @param  string $space
     * @param  string $expectedResponse
     * @return void
     * @requiresRedisJsonVersion >= 1.0.0
     */
    public function testReturnsCorrectJsonResponse(
        array $jsonData,
        string $key,
        string $indent,
        string $newline,
        string $space,
        string $expectedResponse
    ): void {
        $redis = $this->getClient();

        $redis->jsonset(...$jsonData);
        $this->assertSame($expectedResponse, $redis->jsonget($key, $indent, $newline, $space));
    }

    /**
     * @group connected
     * @return void
     * @requiresRedisJsonVersion >= 2.6.1
     */
    public function testReturnsCorrectJsonResponseResp3(): void
    {
        $redis = $this->getResp3Client();

        $redis->jsonset('key', '$', '{"key1":"value1","key2":"value2"}');
        $this->assertSame(
            '[{"key1":"value1","key2":"value2"}]',
            $redis->jsonget('key')
        );
    }

    /**
     * @group connected
     * @return void
     * @requiresRedisJsonVersion >= 1.0.0
     */
    public function testReturnsJsonValuesArrayOnMultiplePathsProvided(): void
    {
        $redis = $this->getClient();

        $redis->jsonset('key', '$', '{"key1":"value1","key2":{"key3":"value3"}}');
        $actualResponse = $redis->jsonget('key', '', '', '', '$.key1', '$..key3');

        $this->assertStringContainsString('"$.key1":["value1"]', $actualResponse);
        $this->assertStringContainsString('"$..key3":["value3"]', $actualResponse);
    }

    /**
     * @group connected
     * @dataProvider unexpectedValuesProvider
     * @param  array  $arguments
     * @param  string $expectedExceptionMessage
     * @return void
     * @requiresRedisJsonVersion >= 1.0.0
     */
    public function testThrowsExceptionOnUnexpectedValueGiven(array $arguments, string $expectedExceptionMessage): void
    {
        $redis = $this->getClient();

        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage($expectedExceptionMessage);

        $redis->jsonget(...$arguments);
    }

    public function argumentsProvider(): array
    {
        return [
            'with default arguments' => [
                ['key'],
                ['key'],
            ],
            'with INDENT modifier' => [
                ['key', '\t'],
                ['key', 'INDENT', '\t'],
            ],
            'with NEWLINE modifier' => [
                ['key', '', '\n'],
                ['key', 'NEWLINE', '\n'],
            ],
            'with SPACE modifier' => [
                ['key', '', '', ' '],
                ['key', 'SPACE', ' '],
            ],
            'with multiple paths' => [
                ['key', '', '', '', 'path1', 'path2'],
                ['key', 'path1', 'path2'],
            ],
            'with all arguments' => [
                ['key', '\t', '\n', ' '],
                ['key', 'INDENT', '\t', 'NEWLINE', '\n', 'SPACE', ' '],
            ],
        ];
    }

    public function jsonProvider(): array
    {
        return [
            'with key only' => [
                ['key', '$', '{"key1":"value1","key2":"value2"}'],
                'key',
                '',
                '',
                '',
                '{"key1":"value1","key2":"value2"}',
            ],
            'with INDENT modifier only' => [
                ['key', '$', '{"key1":"value1","key2":"value2"}'],
                'key',
                '\t',
                '',
                '',
                '{\t"key1":"value1",\t"key2":"value2"}',
            ],
            'with NEWLINE modifier only' => [
                ['key', '$', '{"key1":"value1","key2":"value2"}'],
                'key',
                '',
                '\n',
                '',
                '{\n"key1":"value1",\n"key2":"value2"\n}',
            ],
            'with SPACE modifier only' => [
                ['key', '$', '{"key1":"value1","key2":"value2"}'],
                'key',
                '',
                '',
                ' ',
                '{"key1": "value1","key2": "value2"}',
            ],
            'with all modifiers' => [
                ['key', '$', '{"key1":"value1","key2":"value2"}'],
                'key',
                '\t',
                '\n',
                ' ',
                '{\n\t"key1": "value1",\n\t"key2": "value2"\n}',
            ],
        ];
    }

    public function unexpectedValuesProvider(): array
    {
        return [
            'with wrong INDENT modifier' => [
                ['key', 1, '', ''],
                'Indent argument value should be a string',
            ],
            'with wrong NEWLINE modifier' => [
                ['key', '', 1, ''],
                'Newline argument value should be a string',
            ],
            'with wrong SPACE modifier' => [
                ['key', '', '', 1],
                'Space argument value should be a string',
            ],
        ];
    }
}
