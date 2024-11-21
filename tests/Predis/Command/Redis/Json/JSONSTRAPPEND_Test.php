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

namespace Predis\Command\Redis\Json;

use Predis\Command\Redis\PredisCommandTestCase;

/**
 * @group commands
 * @group realm-stack
 */
class JSONSTRAPPEND_Test extends PredisCommandTestCase
{
    /**
     * {@inheritDoc}
     */
    protected function getExpectedCommand(): string
    {
        return JSONSTRAPPEND::class;
    }

    /**
     * {@inheritDoc}
     */
    protected function getExpectedId(): string
    {
        return 'JSONSTRAPPEND';
    }

    /**
     * @group disconnected
     */
    public function testFilterArguments(): void
    {
        $arguments = ['key', '$..', 'value'];
        $expected = ['key', '$..', 'value'];

        $command = $this->getCommand();
        $command->setArguments($arguments);

        $this->assertSame($expected, $command->getArguments());
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
     * @group relay-resp3
     * @dataProvider jsonProvider
     * @param  array  $jsonArguments
     * @param  string $key
     * @param  string $path
     * @param  string $value
     * @param  array  $expectedStringLength
     * @param  string $expectedModifiedJson
     * @return void
     * @requiresRedisJsonVersion >= 1.0.0
     */
    public function testAppendStringToExistingJsonString(
        array $jsonArguments,
        string $key,
        string $path,
        string $value,
        array $expectedStringLength,
        string $expectedModifiedJson
    ): void {
        $redis = $this->getClient();

        $redis->jsonset(...$jsonArguments);
        $actualResponse = $redis->jsonstrappend($key, $path, $value);

        $this->assertSame($expectedStringLength, $actualResponse);
        $this->assertSame($expectedModifiedJson, $redis->jsonget($key));
    }

    public function jsonProvider(): array
    {
        return [
            'appends to json string on root level' => [
                ['key', '$', '{"key1":"value1","key2":"value2"}'],
                'key',
                '$.key2',
                '"foo"',
                [9],
                '{"key1":"value1","key2":"value2foo"}',
            ],
            'appends to json string on nested level' => [
                ['key', '$', '{"key1":{"key2":"value2"}}'],
                'key',
                '$..key2',
                '"foo"',
                [9],
                '{"key1":{"key2":"value2foo"}}',
            ],
            'appends to json string on both levels' => [
                ['key', '$', '{"key1":{"key2":"value2"},"key2":"value2"}'],
                'key',
                '$..key2',
                '"foo"',
                [9, 9],
                '{"key1":{"key2":"value2foo"},"key2":"value2foo"}',
            ],
            'appends to non-json string' => [
                ['key', '$', '{"key1":{"key2":[1,2,3]}}'],
                'key',
                '$..key2',
                '"foo"',
                [null],
                '{"key1":{"key2":[1,2,3]}}',
            ],
        ];
    }
}
