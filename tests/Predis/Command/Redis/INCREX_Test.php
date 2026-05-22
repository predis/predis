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

use Predis\Command\PrefixableCommand;
use stdClass;
use UnexpectedValueException;

/**
 * @group commands
 * @group realm-string
 */
class INCREX_Test extends PredisCommandTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getExpectedCommand(): string
    {
        return INCREX::class;
    }

    /**
     * {@inheritdoc}
     */
    protected function getExpectedId(): string
    {
        return 'INCREX';
    }

    /**
     * @group disconnected
     * @dataProvider argumentsProvider
     */
    public function testFilterArguments(array $actual, array $expected): void
    {
        $command = $this->getCommand();
        $command->setArguments($actual);

        $this->assertSame($expected, $command->getArguments());
    }

    public function argumentsProvider(): array
    {
        return [
            'int value BYINT' => [
                ['key', 5],
                ['key', 'BYINT', 5],
            ],
            'negative int BYINT' => [
                ['key', -3],
                ['key', 'BYINT', -3],
            ],
            'float value BYFLOAT' => [
                ['key', 1.5],
                ['key', 'BYFLOAT', 1.5],
            ],
            'numeric string without decimal BYINT' => [
                ['key', '5'],
                ['key', 'BYINT', '5'],
            ],
            'numeric string with decimal BYFLOAT' => [
                ['key', '1.5'],
                ['key', 'BYFLOAT', '1.5'],
            ],
            'numeric string with exponent BYFLOAT' => [
                ['key', '1e3'],
                ['key', 'BYFLOAT', '1e3'],
            ],
            'with LBOUND and UBOUND' => [
                ['key', 5, 0, 100],
                ['key', 'BYINT', 5, 'LBOUND', 0, 'UBOUND', 100],
            ],
            'with LBOUND only' => [
                ['key', 1, 0],
                ['key', 'BYINT', 1, 'LBOUND', 0],
            ],
            'with UBOUND only' => [
                ['key', 1, null, 100],
                ['key', 'BYINT', 1, 'UBOUND', 100],
            ],
            'with SATURATE' => [
                ['key', 5, null, 100, true],
                ['key', 'BYINT', 5, 'UBOUND', 100, 'SATURATE'],
            ],
            'without SATURATE (default reject)' => [
                ['key', 5, null, 100, false],
                ['key', 'BYINT', 5, 'UBOUND', 100],
            ],
            'with EX expiration' => [
                ['key', 1, null, null, false, 'EX', 60],
                ['key', 'BYINT', 1, 'EX', 60],
            ],
            'with PERSIST' => [
                ['key', 1, null, null, false, 'PERSIST'],
                ['key', 'BYINT', 1, 'PERSIST'],
            ],
            'with ENX flag' => [
                ['key', 1, null, null, false, 'EX', 60, true],
                ['key', 'BYINT', 1, 'EX', 60, 'ENX'],
            ],
            'all options with int' => [
                ['key', 5, 0, 100, true, 'PX', 5000, true],
                ['key', 'BYINT', 5, 'LBOUND', 0, 'UBOUND', 100, 'SATURATE', 'PX', 5000, 'ENX'],
            ],
            'all options with float' => [
                ['key', 1.5, 0, 100, true, 'PX', 5000, true],
                ['key', 'BYFLOAT', 1.5, 'LBOUND', 0, 'UBOUND', 100, 'SATURATE', 'PX', 5000, 'ENX'],
            ],
        ];
    }

    /**
     * @group disconnected
     */
    public function testThrowsExceptionOnNonNumericStringValue(): void
    {
        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('numeric string');

        $command = $this->getCommand();
        $command->setArguments(['key', 'not-a-number']);
    }

    /**
     * @group disconnected
     */
    public function testThrowsExceptionOnNullValue(): void
    {
        $this->expectException(UnexpectedValueException::class);

        $command = $this->getCommand();
        $command->setArguments(['key', null]);
    }

    /**
     * @group disconnected
     */
    public function testThrowsExceptionOnInvalidValueType(): void
    {
        $this->expectException(UnexpectedValueException::class);

        $command = $this->getCommand();
        $command->setArguments(['key', new stdClass()]);
    }

    /**
     * @group disconnected
     */
    public function testThrowsExceptionOnInvalidExpireType(): void
    {
        $this->expectException(UnexpectedValueException::class);

        $command = $this->getCommand();
        $command->setArguments(['key', 1, null, null, false, 'INVALID', 60]);
    }

    /**
     * @group disconnected
     */
    public function testThrowsExceptionWhenExpireMissingValue(): void
    {
        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('EX requires a value');

        $command = $this->getCommand();
        $command->setArguments(['key', 1, null, null, false, 'EX']);
    }

    /**
     * @group disconnected
     */
    public function testParseResponse(): void
    {
        // BYINT response - ints stay ints
        $this->assertSame([5, 1], $this->getCommand()->parseResponse([5, 1]));

        // RESP2 BYFLOAT response - bulk strings converted to native floats
        $this->assertSame([5.5, 1.5], $this->getCommand()->parseResponse(['5.5', '1.5']));

        // RESP3 BYFLOAT response - already native floats, untouched
        $this->assertSame([5.5, 1.5], $this->getCommand()->parseResponse([5.5, 1.5]));

        // Scientific notation
        $this->assertSame([1000.0, 500.0], $this->getCommand()->parseResponse(['1e3', '5e2']));

        // Non-array response - passed through (e.g. null)
        $this->assertNull($this->getCommand()->parseResponse(null));
    }

    /**
     * @group disconnected
     */
    public function testPrefixKeys(): void
    {
        /** @var PrefixableCommand $command */
        $command = $this->getCommand();
        $actualArguments = ['arg1', 5];
        $prefix = 'prefix:';
        $expectedArguments = ['prefix:arg1', 'BYINT', 5];

        $command->setArguments($actualArguments);
        $command->prefixKeys($prefix);

        $this->assertSame($expectedArguments, $command->getArguments());
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 8.8.0
     */
    public function testIncrementsByIntegerValue(): void
    {
        $redis = $this->getClient();

        $redis->set('cnt', 10);

        $this->assertSame([15, 5], $redis->increx('cnt', 5));
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 8.8.0
     */
    public function testIncrementsByFloatValue(): void
    {
        $redis = $this->getClient();

        $redis->set('cnt', '10');

        $this->assertSame([11.5, 1.5], $redis->increx('cnt', 1.5));
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 8.8.0
     */
    public function testIncrementsByNumericIntegerString(): void
    {
        $redis = $this->getClient();

        $redis->set('cnt', 10);

        $this->assertSame([17, 7], $redis->increx('cnt', '7'));
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 8.8.0
     */
    public function testIncrementsByNumericFloatString(): void
    {
        $redis = $this->getClient();

        $redis->set('cnt', '10');

        $this->assertSame([12.5, 2.5], $redis->increx('cnt', '2.5'));
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 8.8.0
     */
    public function testRespectsLowerAndUpperBounds(): void
    {
        $redis = $this->getClient();

        $redis->set('cnt', 5);

        $this->assertSame([8, 3], $redis->increx('cnt', 3, 0, 100));
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 8.8.0
     */
    public function testSaturateClampsResultToBound(): void
    {
        $redis = $this->getClient();

        $redis->set('cnt', 10);

        $this->assertSame([50, 40], $redis->increx('cnt', 1000, null, 50, true));
        $this->assertSame('50', $redis->get('cnt'));
    }

    /**
     * Without SATURATE, an out-of-bounds operation is rejected silently:
     * the key value and TTL remain unchanged and the reply is [current_value, 0].
     *
     * @group connected
     * @requiresRedisVersion >= 8.8.0
     */
    public function testDefaultRejectLeavesValueUnchanged(): void
    {
        $redis = $this->getClient();

        $redis->set('cnt', 10);

        $this->assertSame([10, 0], $redis->increx('cnt', 1000, null, 50));
        $this->assertSame('10', $redis->get('cnt'));
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 8.8.0
     */
    public function testExpirationWithPx(): void
    {
        $redis = $this->getClient();

        $redis->set('cnt', 10);

        $redis->increx('cnt', 1, null, null, false, 'PX', 60000);
        $this->assertGreaterThan(0, $redis->pttl('cnt'));
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 8.8.0
     */
    public function testExpirationWithPxat(): void
    {
        $redis = $this->getClient();

        $redis->set('cnt', 10);

        $future = (int) ((microtime(true) + 60) * 1000);

        $redis->increx('cnt', 1, null, null, false, 'PXAT', $future);
        $this->assertGreaterThan(0, $redis->pttl('cnt'));
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 8.8.0
     */
    public function testPersistRemovesTtl(): void
    {
        $redis = $this->getClient();

        $redis->set('cnt', 10);
        $redis->expire('cnt', 60);
        $this->assertGreaterThan(0, $redis->ttl('cnt'));

        $redis->increx('cnt', 1, null, null, false, 'PERSIST');
        $this->assertSame(-1, $redis->ttl('cnt'));
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 8.8.0
     */
    public function testEnxSetsExpirationOnlyWhenAbsent(): void
    {
        $redis = $this->getClient();

        $redis->set('cnt', 10);

        $redis->increx('cnt', 1, null, null, false, 'EX', 60, true);
        $firstTtl = $redis->ttl('cnt');
        $this->assertGreaterThan(0, $firstTtl);

        $redis->increx('cnt', 1, null, null, false, 'EX', 9999, true);
        $secondTtl = $redis->ttl('cnt');
        $this->assertLessThanOrEqual($firstTtl, $secondTtl);
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 8.8.0
     */
    public function testIncrementsByIntegerValueResp3(): void
    {
        $redis = $this->getResp3Client();

        $redis->set('cnt', 10);

        $this->assertSame([15, 5], $redis->increx('cnt', 5));
    }

    /**
     * RESP2 returns BYFLOAT results as bulk strings while RESP3 returns native
     * doubles. After parseResponse, callers should see the same native numeric
     * types regardless of protocol.
     *
     * @group connected
     * @requiresRedisVersion >= 8.8.0
     */
    public function testResponseTypesAreConsistentAcrossResp2AndResp3(): void
    {
        $resp2 = $this->getClient();
        $resp2->set('cnt', '10');
        $resp2Float = $resp2->increx('cnt', 1.5);
        $resp2->set('cnt', 10);
        $resp2Int = $resp2->increx('cnt', 5);

        $resp3 = $this->getResp3Client(false);
        $resp3->set('cnt', '10');
        $resp3Float = $resp3->increx('cnt', 1.5);
        $resp3->set('cnt', 10);
        $resp3Int = $resp3->increx('cnt', 5);

        // BYFLOAT: both protocols yield native floats after normalization
        $this->assertSame($resp2Float, $resp3Float);
        $this->assertIsFloat($resp2Float[0]);
        $this->assertIsFloat($resp2Float[1]);
        $this->assertIsFloat($resp3Float[0]);
        $this->assertIsFloat($resp3Float[1]);

        // BYINT: both protocols yield native ints
        $this->assertSame($resp2Int, $resp3Int);
        $this->assertIsInt($resp2Int[0]);
        $this->assertIsInt($resp2Int[1]);
        $this->assertIsInt($resp3Int[0]);
        $this->assertIsInt($resp3Int[1]);
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 8.8.0
     */
    public function testThrowsExceptionOnWrongType(): void
    {
        $this->expectException('Predis\Response\ServerException');
        $this->expectExceptionMessage('Operation against a key holding the wrong kind of value');

        $redis = $this->getClient();

        $redis->lpush('foo', ['bar']);
        $redis->increx('foo', 1);
    }
}
