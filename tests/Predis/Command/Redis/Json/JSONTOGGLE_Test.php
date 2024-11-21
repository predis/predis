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
class JSONTOGGLE_Test extends PredisCommandTestCase
{
    /**
     * {@inheritDoc}
     */
    protected function getExpectedCommand(): string
    {
        return JSONTOGGLE::class;
    }

    /**
     * {@inheritDoc}
     */
    protected function getExpectedId(): string
    {
        return 'JSONTOGGLE';
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
     * @param  array  $expectedResponse
     * @param  string $expectedModifiedJson
     * @return void
     * @requiresRedisJsonVersion >= 2.0.0
     */
    public function testToggleChangesBooleanValueToOpposite(
        array $jsonArguments,
        string $key,
        string $path,
        array $expectedResponse,
        string $expectedModifiedJson
    ): void {
        $redis = $this->getClient();

        $redis->jsonset(...$jsonArguments);
        $actualResponse = $redis->jsontoggle($key, $path);

        $this->assertSame($expectedResponse, $actualResponse);
        $this->assertSame($expectedModifiedJson, $redis->jsonget($key));
    }

    public function jsonProvider(): array
    {
        return [
            'on root level' => [
                ['key', '$', '{"key1":true}'],
                'key',
                '$.key1',
                [0],
                '{"key1":false}',
            ],
            'on nested level' => [
                ['key', '$', '{"key1":{"key2":false}}'],
                'key',
                '$..key2',
                [1],
                '{"key1":{"key2":true}}',
            ],
            'with same keys on both levels' => [
                ['key', '$', '{"key1":{"key2":false},"key2":true}'],
                'key',
                '$..key2',
                [0, 1],
                '{"key1":{"key2":true},"key2":false}',
            ],
            'on non-boolean value' => [
                ['key', '$', '{"key1":"value1"}'],
                'key',
                '$.key1',
                [null],
                '{"key1":"value1"}',
            ],
            'on wrong path' => [
                ['key', '$', '{"key1":"value1"}'],
                'key',
                '$.key2',
                [],
                '{"key1":"value1"}',
            ],
        ];
    }
}
