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
class JSONTYPE_Test extends PredisCommandTestCase
{
    /**
     * {@inheritDoc}
     */
    protected function getExpectedCommand(): string
    {
        return JSONTYPE::class;
    }

    /**
     * {@inheritDoc}
     */
    protected function getExpectedId(): string
    {
        return 'JSONTYPE';
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
     * @param  array  $expectedType
     * @return void
     * @requiresRedisJsonVersion >= 1.0.0
     */
    public function testReturnsJsonValueType(
        array $jsonArguments,
        string $key,
        string $path,
        array $expectedType
    ): void {
        $redis = $this->getClient();

        $redis->jsonset(...$jsonArguments);

        $this->assertSame($expectedType, $redis->jsontype($key, $path));
    }

    public function jsonProvider(): array
    {
        return [
            'with string value' => [
                ['key', '$', '{"key1":"value1"}'],
                'key',
                '$.key1',
                ['string'],
            ],
            'with numeric value' => [
                ['key', '$', '{"key1":1}'],
                'key',
                '$.key1',
                ['integer'],
            ],
            'with array value' => [
                ['key', '$', '{"key1":[1,2,3]}'],
                'key',
                '$.key1',
                ['array'],
            ],
            'with json object value' => [
                ['key', '$', '{"key1":{"key2":"value2"}}'],
                'key',
                '$.key1',
                ['object'],
            ],
            'with boolean value' => [
                ['key', '$', '{"key1":true}'],
                'key',
                '$.key1',
                ['boolean'],
            ],
            'with null value' => [
                ['key', '$', '{"key1":null}'],
                'key',
                '$.key1',
                ['null'],
            ],
        ];
    }
}
