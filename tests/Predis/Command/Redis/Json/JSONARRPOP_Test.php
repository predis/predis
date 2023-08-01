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
class JSONARRPOP_Test extends PredisCommandTestCase
{
    /**
     * {@inheritDoc}
     */
    protected function getExpectedCommand(): string
    {
        return JSONARRPOP::class;
    }

    /**
     * {@inheritDoc}
     */
    protected function getExpectedId(): string
    {
        return 'JSONARRPOP';
    }

    /**
     * @group disconnected
     */
    public function testFilterArguments(): void
    {
        $arguments = ['key', '$..', 2];
        $expected = ['key', '$..', 2];

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
     * @dataProvider jsonProvider
     * @param  array  $jsonArguments
     * @param  string $key
     * @param  string $path
     * @param  int    $index
     * @param  array  $expectedPoppedElements
     * @param  string $expectedModifiedJson
     * @return void
     * @requiresRedisJsonVersion >= 1.0.0
     */
    public function testRemovesElementFromIndexOfJsonArray(
        array $jsonArguments,
        string $key,
        string $path,
        int $index,
        array $expectedPoppedElements,
        string $expectedModifiedJson
    ): void {
        $redis = $this->getClient();

        $redis->jsonset(...$jsonArguments);
        $actualResponse = $redis->jsonarrpop($key, $path, $index);

        $this->assertSame($expectedPoppedElements, $actualResponse);
        $this->assertSame($expectedModifiedJson, $redis->jsonget($key));
    }

    /**
     * @group connected
     * @return void
     * @requiresRedisJsonVersion >= 2.6.1
     */
    public function testRemovesElementFromIndexOfJsonArrayResp3(): void
    {
        $redis = $this->getResp3Client();

        $redis->jsonset('key', '$', '{"key1":"value1","key2":["value1","value2"]}');
        $actualResponse = $redis->jsonarrpop('key', '$.key2', -1);

        $this->assertSame(['"value2"'], $actualResponse);
        $this->assertSame('[{"key1":"value1","key2":["value1"]}]', $redis->jsonget('key'));
    }

    public function jsonProvider(): array
    {
        return [
            'removes last element' => [
                ['key', '$', '{"key1":"value1","key2":["value1","value2"]}'],
                'key',
                '$.key2',
                -1,
                ['"value2"'],
                '{"key1":"value1","key2":["value1"]}',
            ],
            'removes i-element' => [
                ['key', '$', '{"key1":"value1","key2":["value1","value2"]}'],
                'key',
                '$.key2',
                0,
                ['"value1"'],
                '{"key1":"value1","key2":["value2"]}',
            ],
            'removes elements from root and nested levels' => [
                ['key', '$', '{"key1":{"key2":["value1","value2"]},"key2":["value1","value2"]}'],
                'key',
                '$..key2',
                -1,
                ['"value2"', '"value2"'],
                '{"key1":{"key2":["value1"]},"key2":["value1"]}',
            ],
            'removes element from non-array' => [
                ['key', '$', '{"key1":"value1","key2":"value2"}'],
                'key',
                '$.key2',
                -1,
                [null],
                '{"key1":"value1","key2":"value2"}',
            ],
        ];
    }
}
