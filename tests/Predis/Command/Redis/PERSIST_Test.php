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
 * @group realm-key
 */
class PERSIST_Test extends PredisCommandTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getExpectedCommand(): string
    {
        return 'Predis\Command\Redis\PERSIST';
    }

    /**
     * {@inheritdoc}
     */
    protected function getExpectedId(): string
    {
        return 'PERSIST';
    }

    /**
     * @group disconnected
     */
    public function testFilterArguments(): void
    {
        $arguments = array('key');
        $expected = array('key');

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
     * @requiresRedisVersion >= 2.2.0
     */
    public function testRemovesExpireFromKey(): void
    {
        $redis = $this->getClient();

        $redis->set('foo', 'bar');
        $redis->expire('foo', 10);

        $this->assertSame(1, $redis->persist('foo'));
        $this->assertSame(-1, $redis->ttl('foo'));
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 2.2.0
     */
    public function testReturnsZeroOnNonExpiringKeys(): void
    {
        $redis = $this->getClient();

        $redis->set('foo', 'bar');

        $this->assertSame(0, $redis->persist('foo'));
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 2.2.0
     */
    public function testReturnsZeroOnNonExistentKeys(): void
    {
        $redis = $this->getClient();

        $this->assertSame(0, $redis->persist('foo'));
    }
}
