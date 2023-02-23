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

namespace Predis\Command\Redis;

/**
 * @group commands
 * @group realm-string
 */
class BITCOUNT_Test extends PredisCommandTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getExpectedCommand(): string
    {
        return 'Predis\Command\Redis\BITCOUNT';
    }

    /**
     * {@inheritdoc}
     */
    protected function getExpectedId(): string
    {
        return 'BITCOUNT';
    }

    /**
     * @group disconnected
     */
    public function testFilterArguments(): void
    {
        $arguments = ['key', 0, 10, 'bit'];
        $expected = ['key', 0, 10, 'BIT'];

        $command = $this->getCommand();
        $command->setArguments($arguments);

        $this->assertSame($expected, $command->getArguments());
    }

    /**
     * @group disconnected
     */
    public function testParseResponse(): void
    {
        $raw = 10;
        $expected = 10;

        $command = $this->getCommand();

        $this->assertSame($expected, $command->parseResponse($raw));
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 2.6.0
     */
    public function testReturnsNumberOfBitsSet(): void
    {
        $redis = $this->getClient();

        $redis->setbit('key', 1, 1);
        $redis->setbit('key', 10, 1);
        $redis->setbit('key', 16, 1);
        $redis->setbit('key', 22, 1);
        $redis->setbit('key', 32, 1);

        $this->assertSame(5, $redis->bitcount('key'), 'Count bits set (without range)');
        $this->assertSame(3, $redis->bitcount('key', 2, 4), 'Count bits set (with range)');
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 7.0.0
     */
    public function testReturnsNumberOfBitsSetWithExplicitBitByteArgument(): void
    {
        $redis = $this->getClient();

        $redis->setbit('key', 1, 1);
        $redis->setbit('key', 10, 1);
        $redis->setbit('key', 16, 1);
        $redis->setbit('key', 22, 1);
        $redis->setbit('key', 32, 1);

        $this->assertSame(2, $redis->bitcount('key', 0, 10, 'bit'), 'Count bits set (without range)');
        $this->assertSame(1, $redis->bitcount('key', 0, 4, 'bit'), 'Count bits set (with range)');
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 2.6.0
     */
    public function testThrowsExceptionOnWrongType(): void
    {
        $this->expectException('Predis\Response\ServerException');
        $this->expectExceptionMessage('Operation against a key holding the wrong kind of value');

        $redis = $this->getClient();

        $redis->lpush('key', 'list');
        $redis->bitcount('key');
    }
}
