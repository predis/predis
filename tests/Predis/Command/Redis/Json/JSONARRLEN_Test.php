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

/**
 * @group commands
 * @group realm-stack
 */
class JSONARRLEN_Test extends PredisCommandTestCase
{
    /**
     * {@inheritDoc}
     */
    protected function getExpectedCommand(): string
    {
        return JSONARRLEN::class;
    }

    /**
     * {@inheritDoc}
     */
    protected function getExpectedId(): string
    {
        return 'JSONARRLEN';
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
     * @group relay-incompatible
     * @dataProvider jsonProvider
     * @param  array  $jsonArguments
     * @param  string $key
     * @param  string $path
     * @param  array  $expectedLength
     * @return void
     * @requiresRedisJsonVersion >= 1.0.0
     */
    public function testReturnsCorrectJsonArrayLength(
        array $jsonArguments,
        string $key,
        string $path,
        array $expectedLength
    ): void {
        $redis = $this->getClient();

        $redis->jsonset(...$jsonArguments);

        $this->assertSame($expectedLength, $redis->jsonarrlen($key, $path));
    }

    /**
     * @group connected
     * @return void
     * @requiresRedisJsonVersion >= 1.0.0
     */
    public function testReturnsCorrectJsonArrayLengthResp3(): void
    {
        $redis = $this->getResp3Client();

        $redis->jsonset('key', '$', '{"key1":"value1","key2":["value1","value2"]}');

        $this->assertSame([2], $redis->jsonarrlen('key', '$.key2'));
    }

    public function jsonProvider(): array
    {
        return [
            'on root level' => [
                ['key', '$', '{"key1":"value1","key2":["value1","value2"]}'],
                'key',
                '$.key2',
                [2],
            ],
            'on nested level' => [
                ['key', '$', '{"key1":{"key2":["value1","value2"]}}'],
                'key',
                '$..key2',
                [2],
            ],
            'with same keys on both levels' => [
                ['key', '$', '{"key1":{"key2":["value1","value2"]},"key2":["value1","value2","value3"]}'],
                'key',
                '$..key2',
                [3, 2],
            ],
            'with non-array path' => [
                ['key', '$', '{"key1":"value1","key2":"value2"}'],
                'key',
                '$.key2',
                [null],
            ],
        ];
    }
}
