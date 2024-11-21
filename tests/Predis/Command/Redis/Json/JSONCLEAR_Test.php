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
class JSONCLEAR_Test extends PredisCommandTestCase
{
    /**
     * {@inheritDoc}
     */
    protected function getExpectedCommand(): string
    {
        return JSONCLEAR::class;
    }

    /**
     * {@inheritDoc}
     */
    protected function getExpectedId(): string
    {
        return 'JSONCLEAR';
    }

    /**
     * @group disconnected
     */
    public function testFilterArguments(): void
    {
        $arguments = ['key', '$..'];
        $expected = ['key', '$..'];

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
     * @param  int    $expectedClearValues
     * @param  string $expectedModifiedJson
     * @return void
     * @requiresRedisJsonVersion >= 2.0.0
     */
    public function testClearValuesOnArraysAndObjects(
        array $jsonArguments,
        string $key,
        string $path,
        int $expectedClearValues,
        string $expectedModifiedJson
    ): void {
        $redis = $this->getClient();

        $redis->jsonset(...$jsonArguments);
        $actualResponse = $redis->jsonclear($key, $path);

        $this->assertSame($expectedClearValues, $actualResponse);
        $this->assertSame($expectedModifiedJson, $redis->jsonget($key));
    }

    public function jsonProvider(): array
    {
        return [
            'with array values' => [
                ['key', '$', '{"key1":"value1","key2":[1,2,3,4,5,6]}'],
                'key',
                '$.key2',
                1,
                '{"key1":"value1","key2":[]}',
            ],
            'with json object' => [
                ['key', '$', '{"key1":"value1","key2":{"key3":"value3"}}'],
                'key',
                '$.key2',
                1,
                '{"key1":"value1","key2":{}}',
            ],
            'with numeric values' => [
                ['key', '$', '{"key1":"value1","key2":1}'],
                'key',
                '$.key2',
                1,
                '{"key1":"value1","key2":0}',
            ],
            'with all accepted values' => [
                ['key', '$', '{"key1":1,"key2":{"key3":"value3"},"key3":[1,2,3]}'],
                'key',
                '$.*',
                3,
                '{"key1":0,"key2":{},"key3":[]}',
            ],
            'with string and boolean value' => [
                ['key', '$', '{"key1":"value1","key2":true}'],
                'key',
                '$.*',
                0,
                '{"key1":"value1","key2":true}',
            ],
        ];
    }
}
