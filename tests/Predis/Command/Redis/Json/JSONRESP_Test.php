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
use Predis\Response\Status;

/**
 * @group commands
 * @group realm-stack
 */
class JSONRESP_Test extends PredisCommandTestCase
{
    /**
     * {@inheritDoc}
     */
    protected function getExpectedCommand(): string
    {
        return JSONRESP::class;
    }

    /**
     * {@inheritDoc}
     */
    protected function getExpectedId(): string
    {
        return 'JSONRESP';
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
     * @return void
     * @requiresRedisJsonVersion >= 1.0.0
     */
    public function testReturnRespValueFromGivenJson(
        array $jsonArguments,
        string $key,
        string $path,
        array $expectedResponse
    ): void {
        $redis = $this->getClient();

        $redis->jsonset(...$jsonArguments);

        $this->assertEquals($expectedResponse, $redis->jsonresp($key, $path));
    }

    public function jsonProvider(): array
    {
        return [
            'with string value' => [
                ['key', '$', '{"key1":"value1"}'],
                'key',
                '$.key1',
                ['value1'],
            ],
            'with numeric value' => [
                ['key', '$', '{"key1":1}'],
                'key',
                '$.key1',
                [1],
            ],
            'with array value' => [
                ['key', '$', '{"key1":[1,2,3]}'],
                'key',
                '$.key1',
                [[new Status('['), 1, 2, 3]],
            ],
            'with json object value' => [
                ['key', '$', '{"key1":{"key2":"value2"}}'],
                'key',
                '$.key1',
                [[new Status('{'), 'key2', 'value2']],
            ],
            'with boolean value' => [
                ['key', '$', '{"key1":true}'],
                'key',
                '$.key1',
                ['true'],
            ],
            'with null value' => [
                ['key', '$', '{"key1":null}'],
                'key',
                '$.key1',
                [null],
            ],
        ];
    }
}
