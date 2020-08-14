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
class StringSetExpireTest extends PredisCommandTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getExpectedCommand()
    {
        return 'Predis\Command\StringSetExpire';
    }

    /**
     * {@inheritdoc}
     */
    protected function getExpectedId()
    {
        return 'SETEX';
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

        $this->assertEquals('OK', $redis->setex('foo', 10, 'bar'));
        $this->assertSame(1, $redis->exists('foo'));
        $this->assertSame(10, $redis->ttl('foo'));
    }

    /**
     * @medium
     * @group connected
     * @group slow
     */
    public function testKeyExpiresAfterTTL()
    {
        $redis = $this->getClient();

        $this->assertEquals('OK', $redis->setex('foo', 1, 'bar'));

        $this->sleep(2.0);
        $this->assertSame(0, $redis->exists('foo'));
    }

    /**
     * @group connected
     */
    public function testThrowsExceptionOnNonIntegerTTL()
    {
        $this->expectException('Predis\Response\ServerException');
        $this->expectExceptionMessage('ERR value is not an integer or out of range');

        $this->getClient()->setex('foo', 2.5, 'bar');
    }

    /**
     * @group connected
     */
    public function testThrowsExceptionOnZeroTTL()
    {
        $this->expectException('Predis\Response\ServerException');
        $this->expectExceptionMessage('ERR invalid expire time');

        $this->getClient()->setex('foo', 0, 'bar');
    }

    /**
     * @group connected
     */
    public function testThrowsExceptionOnNegativeTTL()
    {
        $this->expectException('Predis\Response\ServerException');
        $this->expectExceptionMessage('ERR invalid expire time');

        $this->getClient()->setex('foo', -10, 'bar');
    }
}
