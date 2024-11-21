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
class JSONMGET_Test extends PredisCommandTestCase
{
    /**
     * {@inheritDoc}
     */
    protected function getExpectedCommand(): string
    {
        return JSONMGET::class;
    }

    /**
     * {@inheritDoc}
     */
    protected function getExpectedId(): string
    {
        return 'JSONMGET';
    }

    /**
     * @group disconnected
     */
    public function testFilterArguments(): void
    {
        $arguments = [['key1', 'key2', 'key3'], 'path'];
        $expected = ['key1', 'key2', 'key3', 'path'];

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
     * @param  array  $firstJson
     * @param  array  $secondJson
     * @param  array  $keys
     * @param  string $path
     * @param  array  $expectedResponse
     * @return void
     * @requiresRedisJsonVersion >= 1.0.0
     */
    public function testMGetReturnsMultipleKeysArguments(
        array $firstJson,
        array $secondJson,
        array $keys,
        string $path,
        array $expectedResponse
    ): void {
        $redis = $this->getClient();

        $redis->jsonset(...$firstJson);
        $redis->jsonset(...$secondJson);

        $this->assertSame($expectedResponse, $redis->jsonmget($keys, $path));
    }

    public function jsonProvider(): array
    {
        return [
            'with both existing paths' => [
                ['key1', '$', '{"key1":"value1","key2":"value2"}'],
                ['key2', '$', '{"key1":"value3","key2":"value2"}'],
                ['key1', 'key2'],
                '$.key1',
                ['["value1"]', '["value3"]'],
            ],
            'with non-existing path' => [
                ['key1', '$', '{"key1":"value1","key2":"value2"}'],
                ['key2', '$', '{"key1":"value3","key3":"value3"}'],
                ['key1', 'key2'],
                '$.key3',
                ['[]', '["value3"]'],
            ],
            'with nested paths - different keys' => [
                ['key1', '$', '{"key1":"value1","key2":{"key3":"value3"}}'],
                ['key2', '$', '{"key1":"value3","key2":{"key3":"value4"}}'],
                ['key1', 'key2'],
                '$..key3',
                ['["value3"]', '["value4"]'],
            ],
            'with nested paths - similar keys' => [
                ['key1', '$', '{"key1":"value1","key2":{"key1":"value3"}}'],
                ['key2', '$', '{"key1":"value3","key2":{"key1":"value4"}}'],
                ['key1', 'key2'],
                '$..key1',
                ['["value1","value3"]', '["value3","value4"]'],
            ],
        ];
    }
}
