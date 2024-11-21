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
class JSONNUMINCRBY_Test extends PredisCommandTestCase
{
    /**
     * {@inheritDoc}
     */
    protected function getExpectedCommand(): string
    {
        return JSONNUMINCRBY::class;
    }

    /**
     * {@inheritDoc}
     */
    protected function getExpectedId(): string
    {
        return 'JSONNUMINCRBY';
    }

    /**
     * @group disconnected
     */
    public function testFilterArguments(): void
    {
        $arguments = ['key', '$..', 5];
        $expected = ['key', '$..', 5];

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
     * @param  int    $value
     * @param  string $expectedIncrementedResponse
     * @return void
     * @requiresRedisJsonVersion >= 1.0.0
     */
    public function testIncrementJsonNumericOnGivenValue(
        array $jsonArguments,
        string $key,
        string $path,
        int $value,
        string $expectedIncrementedResponse
    ): void {
        $redis = $this->getClient();

        $redis->jsonset(...$jsonArguments);

        $this->assertSame($expectedIncrementedResponse, $redis->jsonnumincrby($key, $path, $value));
    }

    public function jsonProvider(): array
    {
        return [
            'on root level' => [
                ['key', '$', '{"key1":"value1","key2":1}'],
                'key',
                '$.key2',
                1,
                '[2]',
            ],
            'on nested level' => [
                ['key', '$', '{"key1":{"key2":5}}'],
                'key',
                '$..key2',
                3,
                '[8]',
            ],
            'on both levels' => [
                ['key', '$', '{"key1":{"key2":5},"key2":4}'],
                'key',
                '$..key2',
                2,
                '[6,7]',
            ],
            'with non-numeric' => [
                ['key', '$', '{"key1":{"key2":[1,2,3]}}'],
                'key',
                '$..key2',
                2,
                '[null]',
            ],
        ];
    }
}
