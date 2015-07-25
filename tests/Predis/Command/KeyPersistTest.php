<?php

/*
 * This file is part of the Predis package.
 *
 * (c) Daniele Alessandri <suppakilla@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Command;

/**
 * @group commands
 * @group realm-key
 */
class KeyPersistTest extends PredisCommandTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getExpectedCommand()
    {
        return 'Predis\Command\KeyPersist';
    }

    /**
     * {@inheritdoc}
     */
    protected function getExpectedId()
    {
        return 'PERSIST';
    }

    /**
     * @group disconnected
     */
    public function testFilterArguments()
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
    public function testParseResponse()
    {
        $command = $this->getCommand();

        $this->assertSame(0, $command->parseResponse(0));
        $this->assertSame(1, $command->parseResponse(1));
    }

    /**
     * @group connected
     */
    public function testRemovesExpireFromKey()
    {
        $redis = $this->getClient();

        $redis->set('foo', 'bar');
        $redis->expire('foo', 10);

        $this->assertSame(1, $redis->persist('foo'));
        $this->assertSame(-1, $redis->ttl('foo'));
    }

    /**
     * @group connected
     */
    public function testReturnsZeroOnNonExpiringKeys()
    {
        $redis = $this->getClient();

        $redis->set('foo', 'bar');

        $this->assertSame(0, $redis->persist('foo'));
    }

    /**
     * @group connected
     */
    public function testReturnsZeroOnNonExistentKeys()
    {
        $redis = $this->getClient();

        $this->assertSame(0, $redis->persist('foo'));
    }
}
