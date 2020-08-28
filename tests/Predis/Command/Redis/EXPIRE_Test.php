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
        $arguments = array('key', 'ttl');
        $expected = array('key', 'ttl');

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
     * @group connected
     */
    public function testDeletesKeysOnNegativeTTL(): void
    {
        $redis = $this->getClient();

        $this->assertEquals('OK', $redis->set('foo', 'bar'));

        $this->assertSame(1, $redis->expire('foo', -10));
        $this->assertSame(0, $redis->exists('foo'));
    }
}
