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

class JSONMERGE_Test extends PredisCommandTestCase
{
    /**
     * {@inheritDoc}
     */
    protected function getExpectedCommand(): string
    {
        return JSONMERGE::class;
    }

    /**
     * {@inheritDoc}
     */
    protected function getExpectedId(): string
    {
        return 'JSONMERGE';
    }

    /**
     * @group disconnected
     */
    public function testFilterArguments(): void
    {
        $arguments = ['key', '$..', '{"a":2}'];
        $expected = ['key', '$..', '{"a":2}'];

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
     * @dataProvider jsonProvider
     * @group connected
     * @group relay-incompatible
     * @param  array  $setArguments
     * @param  array  $mergeArguments
     * @param  string $expectedResponse
     * @return void
     * @requiresRedisJsonVersion >= 2.6.0
     */
    public function testMergeCorrectlyMergeJsonValues(
        array $setArguments,
        array $mergeArguments,
        string $expectedResponse
    ): void {
        $redis = $this->getClient();

        $this->assertEquals('OK', $redis->jsonset(...$setArguments));
        $this->assertEquals('OK', $redis->jsonmerge(...$mergeArguments));
        $this->assertEquals($expectedResponse, $redis->jsonget('key'));
    }

    public function jsonProvider(): array
    {
        return [
            'create non-existing value' => [
                ['key', '$', '{"a":2}'],
                ['key', '$.b', '8'],
                '{"a":2,"b":8}',
            ],
            'replace existing value' => [
                ['key', '$', '{"a":2}'],
                ['key', '$.a', '3'],
                '{"a":3}',
            ],
            'replace an array' => [
                ['key', '$', '{"a":[2,4,6,8]}'],
                ['key', '$.a', '[10,12]'],
                '{"a":[10,12]}',
            ],
            'merge in multiple-paths' => [
                ['key', '$', '{"f1": {"a":1}, "f2":{"a":2}}'],
                ['key', '$', '{"f2":{"a":3, "b":4}, "f3":[2,4,6]}'],
                '{"f1":{"a":1},"f2":{"a":3,"b":4},"f3":[2,4,6]}',
            ],
        ];
    }
}
