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
class SETRANGE_Test extends PredisCommandTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getExpectedCommand(): string
    {
        return 'Predis\Command\Redis\SETRANGE';
    }

    /**
     * {@inheritdoc}
     */
    protected function getExpectedId(): string
    {
        return 'SETRANGE';
    }

    /**
     * @group disconnected
     */
    public function testFilterArguments(): void
    {
        $arguments = array('key', 5, 'range');
        $expected = array('key', 5, 'range');

        $command = $this->getCommand();
        $command->setArguments($arguments);

        $this->assertSame($expected, $command->getArguments());
    }

    /**
     * @group disconnected
     */
    public function testParseResponse(): void
    {
        $this->assertSame(10, $this->getCommand()->parseResponse(10));
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 2.2.0
     */
    public function testCreatesNewKeyOnNonExistingKey(): void
    {
        $redis = $this->getClient();

        $this->assertSame(3, $redis->setrange('foo', 0, 'bar'));
        $this->assertSame('bar', $redis->get('foo'));

        $this->assertSame(8, $redis->setrange('hoge', 4, 'piyo'));
        $this->assertSame("\x00\x00\x00\x00piyo", $redis->get('hoge'));
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 2.2.0
     */
    public function testOverwritesOrAppendBytesInKeys(): void
    {
        $redis = $this->getClient();

        $redis->set('foo', 'barbar');

        $this->assertSame(6, $redis->setrange('foo', 3, 'baz'));
        $this->assertSame('barbaz', $redis->get('foo'));

        $this->assertEquals(16, $redis->setrange('foo', 10, 'foofoo'));
        $this->assertEquals("barbaz\x00\x00\x00\x00foofoo", $redis->get('foo'));
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 2.2.0
     */
    public function testHandlesBinaryData(): void
    {
        $redis = $this->getClient();

        $this->assertSame(4, $redis->setrange('key:binary', 0, pack('i', -2147483648)));

        list($unpacked) = array_values(unpack('i', $redis->get('key:binary')));
        $this->assertEquals(-2147483648, $unpacked);
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 2.2.0
     */
    public function testThrowsExceptionOnInvalidOffset(): void
    {
        $this->expectException('Predis\Response\ServerException');
        $this->expectExceptionMessage('ERR offset is out of range');

        $this->getClient()->setrange('var', -1, 'bogus');
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 2.2.0
     */
    public function testThrowsExceptionOnWrongType(): void
    {
        $this->expectException('Predis\Response\ServerException');
        $this->expectExceptionMessage('Operation against a key holding the wrong kind of value');

        $redis = $this->getClient();

        $redis->lpush('metavars', 'foo');
        $redis->setrange('metavars', 3, 'bar');
    }
}
