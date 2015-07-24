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
class KeyPreciseTimeToLiveTest extends PredisCommandTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getExpectedCommand()
    {
        return 'Predis\Command\KeyPreciseTimeToLive';
    }

    /**
     * {@inheritdoc}
     */
    protected function getExpectedId()
    {
        return 'PTTL';
    }

    /**
     * @group disconnected
     */
    public function testFilterArguments()
    {
        $arguments = array('key', 10);
        $expected = array('key', 10);

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

        $this->assertSame(100, $command->parseResponse(100));
    }

    /**
     * @group connected
     */
    public function testReturnsTTL()
    {
        $redis = $this->getClient();

        $redis->set('foo', 'bar');
        $redis->expire('foo', 10);

        $this->assertLessThanOrEqual(10000, $redis->pttl('foo'));
    }

    /**
     * @group connected
     */
    public function testReturnsLessThanZeroOnNonExpiringKeys()
    {
        $redis = $this->getClient();

        $redis->set('foo', 'bar');
        $this->assertSame(-1, $redis->pttl('foo'));
    }

    /**
     * @group connected
     */
    public function testReturnsLessThanZeroOnNonExistingKeys()
    {
        if ($this->isRedisServerVersion('<', '2.8.0')) {
            $this->assertSame(-1, $this->getClient()->pttl('foo'));
        } else {
            $this->assertSame(-2, $this->getClient()->pttl('foo'));
        }
    }
}
