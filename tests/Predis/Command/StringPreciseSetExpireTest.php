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
 * @group realm-string
 */
class StringPreciseSetExpireTest extends PredisCommandTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getExpectedCommand()
    {
        return 'Predis\Command\StringPreciseSetExpire';
    }

    /**
     * {@inheritdoc}
     */
    protected function getExpectedId()
    {
        return 'PSETEX';
    }

    /**
     * @group disconnected
     */
    public function testFilterArguments()
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
    public function testParseResponse()
    {
        $this->assertSame('OK', $this->getCommand()->parseResponse('OK'));
    }

    /**
     * @group connected
     */
    public function testCreatesNewKeyAndSetsTTL()
    {
        $redis = $this->getClient();

        $this->assertEquals('OK', $redis->psetex('foo', 10000, 'bar'));
        $this->assertSame(1, $redis->exists('foo'));
        $this->assertSame(10, $redis->ttl('foo'));
    }

    /**
     * @group connected
     * @group slow
     */
    public function testKeyExpiresAfterTTL()
    {
        $redis = $this->getClient();

        $this->assertEquals('OK', $redis->psetex('foo', 50, 'bar'));

        $this->sleep(0.5);
        $this->assertSame(0, $redis->exists('foo'));
    }

    /**
     * @group connected
     * @expectedException \Predis\Response\ServerException
     * @expectedExceptionMessage ERR value is not an integer or out of range
     */
    public function testThrowsExceptionOnNonIntegerTTL()
    {
        $this->getClient()->psetex('foo', 2.5, 'bar');
    }

    /**
     * @group connected
     * @expectedException \Predis\Response\ServerException
     * @expectedExceptionMessage ERR invalid expire time
     */
    public function testThrowsExceptionOnZeroTTL()
    {
        $this->getClient()->psetex('foo', 0, 'bar');
    }

    /**
     * @group connected
     * @expectedException \Predis\Response\ServerException
     * @expectedExceptionMessage ERR invalid expire time
     */
    public function testThrowsExceptionOnNegativeTTL()
    {
        $this->getClient()->psetex('foo', -10000, 'bar');
    }
}
