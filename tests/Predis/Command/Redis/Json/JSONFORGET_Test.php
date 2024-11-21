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
class JSONFORGET_Test extends PredisCommandTestCase
{
    /**
     * {@inheritDoc}
     */
    protected function getExpectedCommand(): string
    {
        return JSONFORGET::class;
    }

    /**
     * {@inheritDoc}
     */
    protected function getExpectedId(): string
    {
        return 'JSONFORGET';
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
     * @param  int    $expectedDeleteArgumentsCount
     * @param  string $expectedModifiedJson
     * @return void
     * @requiresRedisJsonVersion >= 1.0.0
     */
    public function testDeletesPathsAtKeyFromGivenJsonString(
        array $jsonArguments,
        string $key,
        string $path,
        int $expectedDeleteArgumentsCount,
        string $expectedModifiedJson
    ): void {
        $redis = $this->getClient();

        $redis->jsonset(...$jsonArguments);
        $actualResponse = $redis->jsonforget($key, $path);

        $this->assertSame($expectedDeleteArgumentsCount, $actualResponse);
        $this->assertSame($expectedModifiedJson, $redis->jsonget($key));
    }

    public function jsonProvider(): array
    {
        return [
            'without nested level' => [
                ['key', '$', '{"key1":"value1","key2":"value2"}'],
                'key',
                '$.key2',
                1,
                '{"key1":"value1"}',
            ],
            'with nested level' => [
                ['key', '$', '{"key1":{"key2":"value2"}}'],
                'key',
                '$..key2',
                1,
                '{"key1":{}}',
            ],
            'with nested level and same key on both levels' => [
                ['key', '$', '{"key1":{"key2":"value2"},"key2":"value2"}'],
                'key',
                '$..key2',
                2,
                '{"key1":{}}',
            ],
            'with wrong path' => [
                ['key', '$', '{"key1":"value1","key2":"value2"}'],
                'key',
                '$.key3',
                0,
                '{"key1":"value1","key2":"value2"}',
            ],
        ];
    }
}
