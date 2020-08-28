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
class SETEX_Test extends PredisCommandTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getExpectedCommand(): string
    {
        return 'Predis\Command\Redis\SETEX';
    }

    /**
     * {@inheritdoc}
     */
    protected function getExpectedId(): string
    {
        return 'SETEX';
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
     * @requiresRedisVersion >= 2.0.0
     */
    public function testCreatesNewKeyAndSetsTTL(): void
    {
        $redis = $this->getClient();

        $this->assertEquals('OK', $redis->setex('foo', 10, 'bar'));
        $this->assertSame(1, $redis->exists('foo'));
        $this->assertSame(10, $redis->ttl('foo'));
    }

    /**
     * @medium
     * @group connected
     * @group slow
     * @requiresRedisVersion >= 2.0.0
     */
    public function testKeyExpiresAfterTTL(): void
    {
        $redis = $this->getClient();

        $this->assertEquals('OK', $redis->setex('foo', 1, 'bar'));

        $this->sleep(2.0);
        $this->assertSame(0, $redis->exists('foo'));
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 2.0.0
     */
    public function testThrowsExceptionOnNonIntegerTTL(): void
    {
        $this->expectException('Predis\Response\ServerException');
        $this->expectExceptionMessage('ERR value is not an integer or out of range');

        $this->getClient()->setex('foo', 2.5, 'bar');
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 2.0.0
     */
    public function testThrowsExceptionOnZeroTTL(): void
    {
        $this->expectException('Predis\Response\ServerException');
        $this->expectExceptionMessage('ERR invalid expire time');

        $this->getClient()->setex('foo', 0, 'bar');
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 2.0.0
     */
    public function testThrowsExceptionOnNegativeTTL(): void
    {
        $this->expectException('Predis\Response\ServerException');
        $this->expectExceptionMessage('ERR invalid expire time');

        $this->getClient()->setex('foo', -10, 'bar');
    }
}
