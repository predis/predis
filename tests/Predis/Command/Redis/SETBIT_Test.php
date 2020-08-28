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
class SETBIT_Test extends PredisCommandTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getExpectedCommand(): string
    {
        return 'Predis\Command\Redis\SETBIT';
    }

    /**
     * {@inheritdoc}
     */
    protected function getExpectedId(): string
    {
        return 'SETBIT';
    }

    /**
     * @group disconnected
     */
    public function testFilterArguments(): void
    {
        $arguments = array('key', 7, 1);
        $expected = array('key', 7, 1);

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
    public function testCanSetBitsOfStrings(): void
    {
        $redis = $this->getClient();

        $redis->set('key:binary', "\x80\x00\00\x01");

        $this->assertEquals(1, $redis->setbit('key:binary', 0, 0));
        $this->assertEquals(0, $redis->setbit('key:binary', 0, 0));
        $this->assertEquals("\x00\x00\00\x01", $redis->get('key:binary'));
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 2.2.0
     */
    public function testCreatesNewKeyOnNonExistingKey(): void
    {
        $redis = $this->getClient();

        $this->assertSame(0, $redis->setbit('key:binary', 31, 1));
        $this->assertSame(0, $redis->setbit('key:binary', 0, 1));
        $this->assertSame(4, $redis->strlen('key:binary'));
        $this->assertSame("\x80\x00\00\x01", $redis->get('key:binary'));
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 2.2.0
     */
    public function testThrowsExceptionOnInvalidBitValue(): void
    {
        $this->expectException('Predis\Response\ServerException');
        $this->expectExceptionMessage('ERR bit is not an integer or out of range');

        $this->getClient()->setbit('key:binary', 10, 255);
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 2.2.0
     */
    public function testThrowsExceptionOnNegativeOffset(): void
    {
        $this->expectException('Predis\Response\ServerException');
        $this->expectExceptionMessage('ERR bit offset is not an integer or out of range');

        $this->getClient()->setbit('key:binary', -1, 1);
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 2.2.0
     */
    public function testThrowsExceptionOnInvalidOffset(): void
    {
        $this->expectException('Predis\Response\ServerException');
        $this->expectExceptionMessage('ERR bit offset is not an integer or out of range');

        $this->getClient()->setbit('key:binary', 'invalid', 1);
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
        $redis->setbit('metavars', 0, 1);
    }
}
