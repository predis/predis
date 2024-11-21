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
class JSONARRAPPEND_Test extends PredisCommandTestCase
{
    /**
     * {@inheritDoc}
     */
    protected function getExpectedCommand(): string
    {
        return JSONARRAPPEND::class;
    }

    /**
     * {@inheritDoc}
     */
    protected function getExpectedId(): string
    {
        return 'JSONARRAPPEND';
    }

    /**
     * @group disconnected
     */
    public function testFilterArguments(): void
    {
        $arguments = ['key', '$..', 5, 'value'];
        $expected = ['key', '$..', 5, 'value'];

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
     * @param  array  $values
     * @param  array  $expectedArrayLength
     * @param  string $expectedModifiedJson
     * @return void
     * @requiresRedisJsonVersion >= 1.0.0
     */
    public function testAppendItemsToGivenJsonArray(
        array $jsonArguments,
        string $key,
        string $path,
        array $values,
        array $expectedArrayLength,
        string $expectedModifiedJson
    ): void {
        $redis = $this->getClient();

        $redis->jsonset(...$jsonArguments);

        $actualResponse = $redis->jsonarrappend($key, $path, ...$values);

        $this->assertSame($expectedArrayLength, $actualResponse);
        $this->assertSame($expectedModifiedJson, $redis->jsonget($key));
    }

    public function jsonProvider(): array
    {
        return [
            'append single item' => [
                ['key', '$', '{"key1":"value1","key2":["value1","value2"]}'],
                'key',
                '$.key2',
                ['"value3"'],
                [3],
                '{"key1":"value1","key2":["value1","value2","value3"]}',
            ],
            'append multiple items - same type' => [
                ['key', '$', '{"key1":"value1","key2":["value1","value2"]}'],
                'key',
                '$.key2',
                ['"value3"', '"value4"'],
                [4],
                '{"key1":"value1","key2":["value1","value2","value3","value4"]}',
            ],
            'append multiple items - different types' => [
                ['key', '$', '{"key1":"value1","key2":["value1","value2"]}'],
                'key',
                '$.key2',
                ['"value3"', '5'],
                [4],
                '{"key1":"value1","key2":["value1","value2","value3",5]}',
            ],
            'append on root and nested level' => [
                ['key', '$', '{"key1":{"key2":["value1","value2"]},"key2":["value1","value2"]}'],
                'key',
                '$..key2',
                ['"value3"', '5'],
                [4, 4],
                '{"key1":{"key2":["value1","value2","value3",5]},"key2":["value1","value2","value3",5]}',
            ],
            'append on root and nested level - not array key' => [
                ['key', '$', '{"key1":{"key2":"value2"},"key2":["value1","value2"]}'],
                'key',
                '$..key2',
                ['"value3"', '5'],
                [4, null],
                '{"key1":{"key2":"value2"},"key2":["value1","value2","value3",5]}',
            ],
        ];
    }
}
