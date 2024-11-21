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
class JSONARRINSERT_Test extends PredisCommandTestCase
{
    /**
     * {@inheritDoc}
     */
    protected function getExpectedCommand(): string
    {
        return JSONARRINSERT::class;
    }

    /**
     * {@inheritDoc}
     */
    protected function getExpectedId(): string
    {
        return 'JSONARRINSERT';
    }

    /**
     * @group disconnected
     */
    public function testFilterArguments(): void
    {
        $arguments = ['key', '$..', 2, 'value'];
        $expected = ['key', '$..', 2, 'value'];

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
     * @param  int    $index
     * @param  array  $values
     * @param  array  $expectedArrayLength
     * @param  string $expectedModifiedArray
     * @return void
     * @requiresRedisJsonVersion >= 1.0.0
     */
    public function testInsertElementIntoJsonArrayBeforeGivenIndex(
        array $jsonArguments,
        string $key,
        string $path,
        int $index,
        array $values,
        array $expectedArrayLength,
        string $expectedModifiedArray
    ): void {
        $redis = $this->getClient();

        $redis->jsonset(...$jsonArguments);

        $actualResponse = $redis->jsonarrinsert($key, $path, $index, ...$values);

        $this->assertSame($expectedArrayLength, $actualResponse);
        $this->assertSame($expectedModifiedArray, $redis->jsonget($key));
    }

    public function jsonProvider(): array
    {
        return [
            'on root level' => [
                ['key', '$', '{"key1":"value1","key2":["value1","value3"]}'],
                'key',
                '$.key2',
                1,
                ['"value2"'],
                [3],
                '{"key1":"value1","key2":["value1","value2","value3"]}',
            ],
            'on nested level' => [
                ['key', '$', '{"key1":{"key2":["value1","value3"]}}'],
                'key',
                '$..key2',
                1,
                ['"value2"'],
                [3],
                '{"key1":{"key2":["value1","value2","value3"]}}',
            ],
            'with both levels matching keys' => [
                ['key', '$', '{"key1":{"key2":["value1","value3"]},"key2":["value1","value3"]}'],
                'key',
                '$..key2',
                1,
                ['"value2"'],
                [3, 3],
                '{"key1":{"key2":["value1","value2","value3"]},"key2":["value1","value2","value3"]}',
            ],
            'with multiple values inserted' => [
                ['key', '$', '{"key1":"value1","key2":["value1","value4"]}'],
                'key',
                '$.key2',
                1,
                ['"value2"', '"value3"'],
                [4],
                '{"key1":"value1","key2":["value1","value2","value3","value4"]}',
            ],
            'with non-array path' => [
                ['key', '$', '{"key1":"value1","key2":"value2"}'],
                'key',
                '$.key2',
                1,
                ['"value2"'],
                [null],
                '{"key1":"value1","key2":"value2"}',
            ],
        ];
    }
}
