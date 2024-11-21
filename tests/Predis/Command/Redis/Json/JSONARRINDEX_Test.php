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
class JSONARRINDEX_Test extends PredisCommandTestCase
{
    /**
     * {@inheritDoc}
     */
    protected function getExpectedCommand(): string
    {
        return JSONARRINDEX::class;
    }

    /**
     * {@inheritDoc}
     */
    protected function getExpectedId(): string
    {
        return 'JSONARRINDEX';
    }

    /**
     * @group disconnected
     */
    public function testFilterArguments(): void
    {
        $arguments = ['key', '$..', 'value', 0, 0];
        $expected = ['key', '$..', 'value', 0, 0];

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
     * @param  int    $start
     * @param  int    $stop
     * @param  array  $expectedIndices
     * @return void
     * @requiresRedisJsonVersion >= 1.0.0
     */
    public function testReturnsCorrectJsonArrayIndex(
        array $jsonArguments,
        string $key,
        string $path,
        string $value,
        int $start,
        int $stop,
        array $expectedIndices
    ): void {
        $redis = $this->getClient();

        $redis->jsonset(...$jsonArguments);
        $this->assertSame($expectedIndices, $redis->jsonarrindex($key, $path, $value, $start, $stop));
    }

    public function jsonProvider(): array
    {
        return [
            'on root level' => [
                ['key', '$', '{"key1":"value1","key2":["value1","value2"]}'],
                'key',
                '$.key2',
                '"value2"',
                0,
                0,
                [1],
            ],
            'on nested level' => [
                ['key', '$', '{"key1":{"key2":["value1","value2"]}}'],
                'key',
                '$..key2',
                '"value2"',
                0,
                0,
                [1],
            ],
            'with both level matching keys' => [
                ['key', '$', '{"key1":{"key2":["value1","value2"]},"key2":["value2"]}'],
                'key',
                '$..key2',
                '"value2"',
                0,
                0,
                [0, 1],
            ],
            'with non-array path' => [
                ['key', '$', '{"key1":"value1","key2":"value2"}'],
                'key',
                '$.key2',
                '"value2"',
                0,
                0,
                [null],
            ],
            'not found - limit by start and stop' => [
                ['key', '$', '{"key1":{"key2":["value1","value2"]},"key2":["value2"]}'],
                'key',
                '$..key2',
                '"value2"',
                0,
                1,
                [0, -1],
            ],
            'not found - with wrong value' => [
                ['key', '$', '{"key1":{"key2":["value1","value2"]},"key2":["value2"]}'],
                'key',
                '$..key2',
                '"value3"',
                0,
                0,
                [-1, -1],
            ],
        ];
    }
}
