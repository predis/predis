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

class HGETEX_Test extends PredisCommandTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getExpectedCommand(): string
    {
        return HGETEX::class;
    }

    /**
     * {@inheritdoc}
     */
    protected function getExpectedId(): string
    {
        return 'HGETEX';
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
    public function testParseResponse(): void
    {
        $command = $this->getCommand();

        $this->assertSame(1, $command->parseResponse(1));
    }

    /**
     * @group connected
     * @dataProvider hashProvider
     * @param  array $hash
     * @param  array $arguments
     * @param  array $expectedResponse
     * @param  float $timeout
     * @return void
     * @requiresRedisVersion >= 7.9.0
     */
    public function testReturnsValueAndSetExpirationTimeForGivenHash(
        array $hash,
        array $arguments,
        array $expectedResponse,
        float $timeout
    ): void {
        $redis = $this->getClient();

        $redis->hset(...$hash);

        $this->assertSame($expectedResponse, $redis->hgetex(...$arguments));
        $this->sleep($timeout);
        $this->assertSame([], $redis->hgetall('hash_key'));
    }

    /**
     * @group connected
     * @return void
     * @requiresRedisVersion >= 7.9.0
     */
    public function testRemovesAssociatedTTLFromHash()
    {
        $redis = $this->getClient();

        $redis->hsetex(
            'hash_key', ['field1' => 'value1'],
            HSETEX::SET_NULL, HSETEX::TTL_EX, 100
        );

        $this->assertGreaterThan(0, $redis->hexpiretime('hash_key', ['field1'])[0]);
        $redis->hgetex('hash_key', ['field1'], HGETEX::PERSIST);
        $this->assertEquals(-1, $redis->hexpiretime('hash_key', ['field1'])[0]);
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

        $redis->hgetex(...$arguments);
    }

    public function argumentsProvider(): array
    {
        return [
            'with default arguments' => [
                ['key', ['field1', 'field2']],
                ['key', 'FIELDS', 2, 'field1', 'field2'],
            ],
            'with EX modifier' => [
                ['key', ['field1', 'field2'], HGETEX::EX, 10],
                ['key', 'EX', 10, 'FIELDS', 2, 'field1', 'field2'],
            ],
            'with PX modifier' => [
                ['key', ['field1', 'field2'], HGETEX::PX, 10],
                ['key', 'PX', 10, 'FIELDS', 2, 'field1', 'field2'],
            ],
            'with EXAT modifier' => [
                ['key', ['field1', 'field2'], HGETEX::EXAT, 10],
                ['key', 'EXAT', 10, 'FIELDS', 2, 'field1', 'field2'],
            ],
            'with PXAT modifier' => [
                ['key', ['field1', 'field2'], HGETEX::PXAT, 10],
                ['key', 'PXAT', 10, 'FIELDS', 2, 'field1', 'field2'],
            ],
            'with PERSIST modifier' => [
                ['key', ['field1', 'field2'], HGETEX::PERSIST],
                ['key', 'PERSIST', 'FIELDS', 2, 'field1', 'field2'],
            ],
        ];
    }

    public function hashProvider(): array
    {
        return [
            'with expiration - EX modifier' => [
                ['hash_key', 'field1', 'value1', 'field2', 'value2'],
                ['hash_key', ['field1', 'field2'], HGETEX::EX, 1],
                ['value1', 'value2'],
                1.2,
            ],
            'with expiration - PX modifier' => [
                ['hash_key', 'field1', 'value1', 'field2', 'value2'],
                ['hash_key', ['field1', 'field2'], HGETEX::PX, 100],
                ['value1', 'value2'],
                0.2,
            ],
            'with expiration - EXAT modifier' => [
                ['hash_key', 'field1', 'value1', 'field2', 'value2'],
                ['hash_key', ['field1', 'field2'], HGETEX::EXAT, time() + 1],
                ['value1', 'value2'],
                2,
            ],
            'with expiration - PXAT modifier' => [
                ['hash_key', 'field1', 'value1', 'field2', 'value2'],
                ['hash_key', ['field1', 'field2'], HGETEX::PXAT, (time() * 1000) + 100],
                ['value1', 'value2'],
                0.3,
            ],
        ];
    }

    public function unexpectedValuesProvider(): array
    {
        return [
            'with wrong modifier' => [
                ['key', ['field1', 'field2'], 'wrong', 10],
                'Modifier argument accepts only: ex, px, exat, pxat, persist values',
            ],
            'with wrong type value' => [
                ['key', ['field1', 'field2'], 'ex', true],
                'Modifier value is missing or incorrect type',
            ],
        ];
    }
}
