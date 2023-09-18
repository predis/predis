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
class JSONOBJKEYS_Test extends PredisCommandTestCase
{
    /**
     * {@inheritDoc}
     */
    protected function getExpectedCommand(): string
    {
        return JSONOBJKEYS::class;
    }

    /**
     * {@inheritDoc}
     */
    protected function getExpectedId(): string
    {
        return 'JSONOBJKEYS';
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
     * @param  array  $expectedKeys
     * @return void
     * @requiresRedisJsonVersion >= 1.0.0
     */
    public function testReturnsKeysForGivenJsonObject(
        array $jsonArguments,
        string $key,
        string $path,
        array $expectedKeys
    ): void {
        $redis = $this->getClient();

        $redis->jsonset(...$jsonArguments);

        $this->assertSame($expectedKeys, $redis->jsonobjkeys($key, $path));
    }

    public function jsonProvider(): array
    {
        return [
            'on root level' => [
                ['key', '$', '{"key1":"value1","key2":"value2"}'],
                'key',
                '$',
                [['key1', 'key2']],
            ],
            'on nested level' => [
                ['key', '$', '{"key1":"value1","key2":{"key1":"value1","key2":"value2"}}'],
                'key',
                '$.key2',
                [['key1', 'key2']],
            ],
            'with same key on both levels' => [
                ['key', '$', '{"key1":{"key3":"value3","key4":"value4"},"key2":{"key1":{"key2":"value2"}}}'],
                'key',
                '$..key1',
                [['key3', 'key4'], ['key2']],
            ],
            'with one of the keys not a JSON object' => [
                ['key', '$', '{"key1":"value1","key2":{"key1":{"key2":"value2"}}}'],
                'key',
                '$..key1',
                [null, ['key2']],
            ],
        ];
    }
}
