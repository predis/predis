<?php

/*
 * This file is part of the Predis package.
 *
 * (c) Daniele Alessandri <suppakilla@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Command\Redis;

/**
 * @group commands
 * @group realm-string
 */
class PSETEX_Test extends PredisCommandTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getExpectedCommand(): string
    {
        return 'Predis\Command\Redis\PSETEX';
    }

    /**
     * {@inheritdoc}
     */
    protected function getExpectedId(): string
    {
        return 'PSETEX';
    }

    /**
     * @group disconnected
     */
    public function testFilterArguments(): void
    {
        $arguments = array('key', 10, 'hello');
        $expected = array('key', 10, 'hello');

        $command = $this->getCommand();
        $command->setArguments($arguments);

        $this->assertSame($expected, $command->getArguments());
    }

    /**
     * @group disconnected
     */
    public function testParseResponse(): void
    {
        $this->assertSame('OK', $this->getCommand()->parseResponse('OK'));
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 2.6.0
     */
    public function testCreatesNewKeyAndSetsTTL(): void
    {
        $redis = $this->getClient();

        $this->assertEquals('OK', $redis->psetex('foo', 10000, 'bar'));
        $this->assertSame(1, $redis->exists('foo'));
        $this->assertSame(10, $redis->ttl('foo'));
    }

    /**
     * @group connected
     * @group slow
     * @requiresRedisVersion >= 2.6.0
     */
    public function testKeyExpiresAfterTTL(): void
    {
        $redis = $this->getClient();

        $this->assertEquals('OK', $redis->psetex('foo', 50, 'bar'));

        $this->sleep(0.5);
        $this->assertSame(0, $redis->exists('foo'));
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 2.6.0
     */
    public function testThrowsExceptionOnNonIntegerTTL(): void
    {
        $this->expectException('Predis\Response\ServerException');
        $this->expectExceptionMessage('ERR value is not an integer or out of range');

        $this->getClient()->psetex('foo', 2.5, 'bar');
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 2.6.0
     */
    public function testThrowsExceptionOnZeroTTL(): void
    {
        $this->expectException('Predis\Response\ServerException');
        $this->expectExceptionMessage('ERR invalid expire time');

        $this->getClient()->psetex('foo', 0, 'bar');
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 2.6.0
     */
    public function testThrowsExceptionOnNegativeTTL(): void
    {
        $this->expectException('Predis\Response\ServerException');
        $this->expectExceptionMessage('ERR invalid expire time');

        $this->getClient()->psetex('foo', -10000, 'bar');
    }
}
