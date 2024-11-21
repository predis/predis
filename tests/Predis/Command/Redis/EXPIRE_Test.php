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

/**
 * @group commands
 * @group realm-key
 */
class EXPIRE_Test extends PredisCommandTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getExpectedCommand(): string
    {
        return 'Predis\Command\Redis\EXPIRE';
    }

    /**
     * {@inheritdoc}
     */
    protected function getExpectedId(): string
    {
        return 'EXPIRE';
    }

    /**
     * @group disconnected
     */
    public function testFilterArguments(): void
    {
        $arguments = ['key', 'ttl', 'xx'];
        $expected = ['key', 'ttl', 'XX'];

        $command = $this->getCommand();
        $command->setArguments($arguments);

        $this->assertSame($expected, $command->getArguments());
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
     * @group connected
     */
    public function testReturnsZeroOnNonExistingKeys(): void
    {
        $redis = $this->getClient();

        $this->assertSame(0, $redis->expire('foo', 2));
    }

    /**
     * @medium
     * @group connected
     * @group slow
     */
    public function testCanExpireKeys(): void
    {
        $redis = $this->getClient();

        $this->assertEquals('OK', $redis->set('foo', 'bar'));

        $this->assertSame(1, $redis->expire('foo', 1));
        $this->assertSame(1, $redis->ttl('foo'));

        $this->sleep(2.0);
        $this->assertSame(0, $redis->exists('foo'));
    }

    /**
     * @medium
     * @group connected
     * @group slow
     * @dataProvider keysProvider
     * @param  array $firstKeyArguments
     * @param  array $secondKeyArguments
     * @param  array $positivePathArguments
     * @param  array $negativePathArguments
     * @return void
     * @requiresRedisVersion >= 7.0.0
     */
    public function testSetNewExpirationTimeWithExpireOptions(
        array $firstKeyArguments,
        array $secondKeyArguments,
        array $positivePathArguments,
        array $negativePathArguments
    ): void {
        $redis = $this->getClient();

        $redis->set(...$firstKeyArguments);
        $redis->set(...$secondKeyArguments);

        $this->assertSame(1, $redis->expire(...$positivePathArguments));
        $this->assertSame(0, $redis->expire(...$negativePathArguments));
    }

    /**
     * @group connected
     */
    public function testDeletesKeysOnNegativeTTL(): void
    {
        $redis = $this->getClient();

        $this->assertEquals('OK', $redis->set('foo', 'bar'));

        $this->assertSame(1, $redis->expire('foo', -10));
        $this->assertSame(0, $redis->exists('foo'));
    }

    public function keysProvider(): array
    {
        return [
            'only if key has no expiry' => [
                ['noExpiry', 'value'],
                ['withExpiry', 'value', 'EX', 10],
                ['noExpiry', 2, 'NX'],
                ['withExpiry', 2, 'NX'],
            ],
            'only if key has expiry' => [
                ['noExpiry', 'value'],
                ['withExpiry', 'value', 'EX', 10],
                ['withExpiry', 2, 'XX'],
                ['noExpiry', 2, 'XX'],
            ],
            'only if new expiry is greater then current one' => [
                ['newExpiryLower', 'value', 'EX', 1000],
                ['newExpiryGreater', 'value', 'EX', 10],
                ['newExpiryGreater', 100, 'GT'],
                ['newExpiryLower', 20, 'GT'],
            ],
            'only if new expiry is lower then current one' => [
                ['newExpiryLower', 'value', 'EX', 1000],
                ['newExpiryGreater', 'value', 'EX', 10],
                ['newExpiryLower', 20, 'LT'],
                ['newExpiryGreater', 20, 'LT'],
            ],
        ];
    }
}
