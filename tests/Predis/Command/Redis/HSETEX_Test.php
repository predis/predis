<?php

/*
 * This file is part of the Predis package.
 *
 * (c) 2009-2020 Daniele Alessandri
 * (c) 2021-2026 Till Krüss
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Command\Redis;

use UnexpectedValueException;

class HSETEX_Test extends PredisCommandTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getExpectedCommand(): string
    {
        return HSETEX::class;
    }

    /**
     * {@inheritdoc}
     */
    protected function getExpectedId(): string
    {
        return 'HSETEX';
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
     * @group connected
     * @group slow
     * @dataProvider hashProvider
     * @param  array $arguments
     * @param  int   $expectedResponse
     * @param  float $timeout
     * @return void
     * @requiresRedisVersion >= 7.9.0
     */
    public function testSetHashWithExpiration(
        array $arguments,
        int $expectedResponse,
        float $timeout
    ): void {
        $redis = $this->getClient();

        $this->assertSame($expectedResponse, $redis->hsetex(...$arguments));

        $this->sleep($timeout);

        $this->assertSame([], $redis->hgetall('hash_key'));
    }

    /**
     * @group connected
     * @return void
     * @requiresRedisVersion >= 7.9.0
     */
    public function testSetHashFieldsIfNotExists(): void
    {
        $redis = $this->getClient();

        $this->assertSame(
            1,
            $redis->hsetex('hash_key', ['field1' => 'value1', 'field2' => 'value2'], HSETEX::SET_FNX)
        );
        $this->assertSame(
            0,
            $redis->hsetex('hash_key', ['field1' => 'value1', 'field2' => 'value2'], HSETEX::SET_FNX)
        );
    }

    /**
     * @group connected
     * @return void
     * @requiresRedisVersion >= 7.9.0
     */
    public function testSetHashFieldsOnlyIfExists(): void
    {
        $redis = $this->getClient();

        $this->assertSame(
            0,
            $redis->hsetex('hash_key', ['field1' => 'value1', 'field2' => 'value2'], HSETEX::SET_FXX)
        );
        $this->assertSame(
            1,
            $redis->hsetex('hash_key', ['field1' => 'value1', 'field2' => 'value2'], HSETEX::SET_FNX)
        );
        $this->assertSame(
            1,
            $redis->hsetex('hash_key', ['field1' => 'value1', 'field2' => 'value2'], HSETEX::SET_FXX)
        );
    }

    /**
     * @group connected
     * @return void
     * @requiresRedisVersion >= 7.9.0
     */
    public function testSetHashFieldRetainingTTLValue(): void
    {
        $redis = $this->getClient();

        $this->assertSame(
            1,
            $redis->hsetex(
                'hash_key',
                ['field1' => 'value1', 'field2' => 'value2'],
                HSETEX::SET_FNX,
                HSETEX::TTL_EX,
                100
            )
        );

        $this->assertGreaterThan(0, $redis->hexpiretime('hash_key', ['field1'])[0]);

        $this->assertSame(
            1,
            $redis->hsetex(
                'hash_key',
                ['field1' => 'value1'],
                HSETEX::SET_FXX,
                HSETEX::TTL_KEEP_TTL
            )
        );

        $this->assertGreaterThan(0, $redis->hexpiretime('hash_key', ['field1'])[0]);
    }

    /**
     * @group connected
     * @dataProvider unexpectedValuesProvider
     * @param  array  $arguments
     * @param  string $expectedExceptionMessage
     * @return void
     */
    public function testThrowsExceptionOnUnexpectedValueGiven(
        array $arguments,
        string $expectedExceptionMessage
    ): void {
        $redis = $this->getClient();

        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage($expectedExceptionMessage);

        $redis->hsetex(...$arguments);
    }

    public function argumentsProvider(): array
    {
        return [
            'with default arguments' => [
                ['key', ['field1' => 'value1', 'field2' => 'value2']],
                ['key', 'FIELDS', 2, 'field1', 'value1', 'field2', 'value2'],
            ],
            'with FNX modifier' => [
                ['key', ['field1' => 'value1', 'field2' => 'value2'], HSETEX::SET_FNX],
                ['key', 'FNX', 'FIELDS', 2, 'field1', 'value1', 'field2', 'value2'],
            ],
            'with FXX modifier' => [
                ['key', ['field1' => 'value1', 'field2' => 'value2'], HSETEX::SET_FXX],
                ['key', 'FXX', 'FIELDS', 2, 'field1', 'value1', 'field2', 'value2'],
            ],
            'with EX modifier' => [
                ['key', ['field1' => 'value1', 'field2' => 'value2'], HSETEX::SET_NULL, HSETEX::TTL_EX, 10],
                ['key', 'EX', 10, 'FIELDS', 2, 'field1', 'value1', 'field2', 'value2'],
            ],
            'with PX modifier' => [
                ['key', ['field1' => 'value1', 'field2' => 'value2'], HSETEX::SET_NULL, HSETEX::TTL_PX, 10],
                ['key', 'PX', 10, 'FIELDS', 2, 'field1', 'value1', 'field2', 'value2'],
            ],
            'with EXAT modifier' => [
                ['key', ['field1' => 'value1', 'field2' => 'value2'], HSETEX::SET_NULL, HSETEX::TTL_EXAT, 10],
                ['key', 'EXAT', 10, 'FIELDS', 2, 'field1', 'value1', 'field2', 'value2'],
            ],
            'with PXAT modifier' => [
                ['key', ['field1' => 'value1', 'field2' => 'value2'], HSETEX::SET_NULL, HSETEX::TTL_PXAT, 10],
                ['key', 'PXAT', 10, 'FIELDS', 2, 'field1', 'value1', 'field2', 'value2'],
            ],
            'with KEEPTTL modifier' => [
                ['key', ['field1' => 'value1', 'field2' => 'value2'], HSETEX::SET_NULL, HSETEX::TTL_KEEP_TTL],
                ['key', 'KEEPTTL', 'FIELDS', 2, 'field1', 'value1', 'field2', 'value2'],
            ],
            'with combined modifiers' => [
                ['key', ['field1' => 'value1', 'field2' => 'value2'], HSETEX::SET_FXX, HSETEX::TTL_PXAT, 10],
                ['key', 'FXX', 'PXAT', 10, 'FIELDS', 2, 'field1', 'value1', 'field2', 'value2'],
            ],
        ];
    }

    public function hashProvider(): array
    {
        return [
            'with EX modifier' => [
                ['key', ['field1' => 'value1', 'field2' => 'value2'], HSETEX::SET_NULL, HSETEX::TTL_EX, 1],
                1,
                1.2,
            ],
            'with PX modifier' => [
                ['key', ['field1' => 'value1', 'field2' => 'value2'], HSETEX::SET_NULL, HSETEX::TTL_PX, 100],
                1,
                0.2,
            ],
            'with EXAT modifier' => [
                ['key', ['field1' => 'value1', 'field2' => 'value2'], HSETEX::SET_NULL, HSETEX::TTL_EXAT, time() + 1],
                1,
                2,
            ],
            'with PXAT modifier' => [
                ['key', ['field1' => 'value1', 'field2' => 'value2'], HSETEX::SET_NULL, HSETEX::TTL_PXAT, (time() * 1000) + 100],
                1,
                0.3,
            ],
            'with combined modifiers' => [
                ['key', ['field1' => 'value1', 'field2' => 'value2'], HSETEX::SET_FNX, HSETEX::TTL_PX, 100],
                1,
                0.2,
            ],
        ];
    }

    public function unexpectedValuesProvider(): array
    {
        return [
            'with wrong set modifier' => [
                ['key', ['field1', 'field2'], 'wrong'],
                'Modifier argument accepts only: fnx, fxx values',
            ],
            'with wrong ttl modifier' => [
                ['key', ['field1', 'field2'], '', 'wrong'],
                'Modifier argument accepts only: ex, px, exat, pxat, keepttl values',
            ],
            'with wrong ttl modifier value' => [
                ['key', ['field1', 'field2'], '', HSETEX::TTL_PXAT, 'wrong'],
                'Modifier value is missing or incorrect type',
            ],
        ];
    }
}
