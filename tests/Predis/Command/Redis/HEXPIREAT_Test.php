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

use UnexpectedValueException;

class HEXPIREAT_Test extends PredisCommandTestCase
{
    /**
     * {@inheritDoc}
     */
    protected function getExpectedCommand(): string
    {
        return HEXPIREAT::class;
    }

    /**
     * {@inheritDoc}
     */
    protected function getExpectedId(): string
    {
        return 'HEXPIREAT';
    }

    /**
     * @group disconnected
     * @dataProvider argumentsProvider
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
    public function testFilterArgumentsThrowsExceptionOnIncorrectFlagValue(): void
    {
        $command = $this->getCommand();

        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('Unsupported flag value');

        $command->setArguments(['key', 1000, null, 'wrong']);
    }

    /**
     * @group disconnected
     */
    public function testParseResponse(): void
    {
        $command = $this->getCommand();

        $this->assertSame(0, $command->parseResponse(0));
        $this->assertSame(1, $command->parseResponse(1));
    }

    /**
     * @dataProvider hashProvider
     * @group connected
     * @group slow
     * @requiresRedisVersion >= 7.3.0
     */
    public function testHashExpiresCorrectlyWithNoFlags(
        array $hashArgs,
        array $expireArgs,
        array $expectedResponse,
        array $expectedHash
    ): void {
        $redis = $this->getClient();

        $redis->hset(...$hashArgs);

        // Time should be calculated within a test, in data provider it will be pre-calculated before test execution.
        $expireArgs[1] = time() + $expireArgs[1];
        $this->assertSame($expectedResponse, $redis->hexpireat(...$expireArgs));
        $this->sleep(2);
        $this->assertSameValues($expectedHash, $redis->hgetall('hashkey'));
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 7.3.0
     */
    public function testHashExpiresCorrectlyWithFlags(): void
    {
        $redis = $this->getClient();

        $redis->hset('hashkey', 'field1', 'value1', 'field2', 'value2');

        $this->assertSame([1, 1], $redis->hexpireat('hashkey', time() + 2, ['field1', 'field2']));
        $this->assertSame([0, 0], $redis->hexpireat('hashkey', time() + 2, ['field1', 'field2'], 'NX'));
        $this->assertSame([1, 1], $redis->hexpireat('hashkey', time() + 2, ['field1', 'field2'], 'XX'));
        $this->assertSame([1, 1], $redis->hexpireat('hashkey', time() + 3, ['field1', 'field2'], 'GT'));
        $this->assertSame([0, 0], $redis->hexpireat('hashkey', time() + 1, ['field1', 'field2'], 'GT'));
        $this->assertSame([1, 1], $redis->hexpireat('hashkey', time() + 2, ['field1', 'field2'], 'LT'));
        $this->assertSame([0, 0], $redis->hexpireat('hashkey', time() + 3, ['field1', 'field2'], 'LT'));
        $this->assertSame([-2, -2], $redis->hexpireat('wrongkey', time() + 2, ['field1', 'field2']));
    }

    public function hashProvider(): array
    {
        return [
            'with all fields expired' => [
                ['hashkey', 'field1', 'value1', 'field2', 'value2'],
                ['hashkey', 1, ['field1', 'field2']],
                [1, 1],
                [],
            ],
            'with partial fields expired' => [
                ['hashkey', 'field1', 'value1', 'field2', 'value2'],
                ['hashkey', 1, ['field1']],
                [1],
                ['field2' => 'value2'],
            ],
            'with incorrect fields' => [
                ['hashkey', 'field1', 'value1', 'field2', 'value2'],
                ['hashkey', 1, ['field3', 'field4']],
                [-2, -2],
                ['field2' => 'value2', 'field1' => 'value1'],
            ],
        ];
    }

    public function argumentsProvider(): array
    {
        return [
            'with specified fields' => [
                ['key', 100, ['field1', 'field2']],
                ['key', 100, 'FIELDS', 2, 'field1', 'field2'],
            ],
            'with specified flag' => [
                ['key', 100, null, 'NX'],
                ['key', 100, 'NX'],
            ],
            'with all arguments' => [
                ['key', 100, ['field1', 'field2'], 'XX'],
                ['key', 100, 'XX', 'FIELDS', 2, 'field1', 'field2'],
            ],
        ];
    }
}
