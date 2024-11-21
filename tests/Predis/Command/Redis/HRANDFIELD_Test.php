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

namespace Predis\Command\Redis;

use Predis\Response\ServerException;

class HRANDFIELD_Test extends PredisCommandTestCase
{
    /**
     * {@inheritDoc}
     */
    protected function getExpectedCommand(): string
    {
        return HRANDFIELD::class;
    }

    /**
     * {@inheritDoc}
     */
    protected function getExpectedId(): string
    {
        return 'HRANDFIELD';
    }

    /**
     * @group disconnected
     * @dataProvider argumentsProvider
     * @param  array $actualArguments
     * @param  array $expectedArguments
     * @return void
     */
    public function testFilterArguments(array $actualArguments, array $expectedArguments): void
    {
        $command = $this->getCommand();
        $command->setArguments($actualArguments);

        $this->assertSame($expectedArguments, $command->getArguments());
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
     * @dataProvider hashesProvider
     * @param  array  $hash
     * @param  string $key
     * @param  int    $count
     * @param  bool   $withValues
     * @param  array  $expectedResponse
     * @return void
     * @requiresRedisVersion >= 6.2.0
     */
    public function testReturnsRandomFieldsFromHash(
        array $hash,
        string $key,
        int $count,
        bool $withValues,
        array $expectedResponse
    ): void {
        $redis = $this->getClient();

        $redis->hset($key, ...$hash);
        $actualResponse = $redis->hrandfield($key, $count, $withValues);

        $this->assertOneOf($expectedResponse, $actualResponse);
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 6.2.0
     */
    public function testThrowsExceptionOnWrongType(): void
    {
        $this->expectException(ServerException::class);
        $this->expectExceptionMessage('Operation against a key holding the wrong kind of value');

        $redis = $this->getClient();

        $redis->set('foo', 'bar');
        $redis->hrandfield('foo');
    }

    public function argumentsProvider(): array
    {
        return [
            'with default arguments' => [
                ['key'],
                ['key'],
            ],
            'with count argument' => [
                ['key', 1],
                ['key', 1],
            ],
            'with WITHVALUES argument' => [
                ['key', 1, true],
                ['key', 1, 'WITHVALUES'],
            ],
        ];
    }

    public function hashesProvider(): array
    {
        return [
            'one field - without values' => [
                ['key1', 'value1', 'key2', 'value2', 'key3', 'value3'],
                'key',
                1,
                false,
                ['key1', 'key2', 'key3'],
            ],
            'one field - with values' => [
                ['key1', 'value1', 'key2', 'value2', 'key3', 'value3'],
                'key',
                1,
                true,
                ['key1', 'value1', 'key2', 'value2', 'key3', 'value3'],
            ],
            'multiple fields - without values' => [
                ['key1', 'value1', 'key2', 'value2', 'key3', 'value3'],
                'key',
                2,
                false,
                ['key1', 'key2', 'key3'],
            ],
            'multiple fields - with values' => [
                ['key1', 'value1', 'key2', 'value2', 'key3', 'value3'],
                'key',
                2,
                true,
                ['key1', 'value1', 'key2', 'value2', 'key3', 'value3'],
            ],
            'multiple fields - allows same fields' => [
                ['key1', 'value1', 'key2', 'value2', 'key3', 'value3'],
                'key',
                -2,
                false,
                ['key1', 'key2', 'key3'],
            ],
        ];
    }
}
