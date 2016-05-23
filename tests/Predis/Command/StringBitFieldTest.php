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
class StringBitFieldTest extends PredisCommandTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getExpectedCommand()
    {
        return 'Predis\Command\StringBitField';
    }

    /**
     * {@inheritdoc}
     */
    protected function getExpectedId()
    {
        return 'BITFIELD';
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
    public function testFilterMultipleArguments()
    {
        $arguments = array('key', 'incrby', 'u2', '100', '1', 'OVERFLOW', 'SAT', 'incrby', 'u2', '102', '1', 'GET', 'u2', '100');
        $expected = array('key', 'incrby', 'u2', '100', '1', 'OVERFLOW', 'SAT', 'incrby', 'u2', '102', '1', 'GET', 'u2', '100');

        $command = $this->getCommand();
        $command->setArguments($arguments);

        $this->assertSame($expected, $command->getArguments());
    }

    /**
     * @group disconnected
     */
    public function testParseResponse()
    {
        $raw = array(1);
        $expected = array(1);

        $command = $this->getCommand();

        $this->assertSame($expected, $command->parseResponse($raw));
    }

    /**
     * @group disconnected
     */
    public function testParseResponseComplex()
    {
        $raw = array(1, 0, 3);
        $expected = array(1, 0, 3);

        $command = $this->getCommand();

        $this->assertSame($expected, $command->parseResponse($raw));
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 3.2.0
     */
    public function testBitfieldWithGetModifier()
    {
        $redis = $this->getClient();

        $redis->setbit('string', 0, 1);
        $redis->setbit('string', 8, 1);

        $this->assertSame(array(128), $redis->bitfield('string', 'GET', 'u8', 0));
        $this->assertSame(array(128, 1, 128), $redis->bitfield('string', 'GET', 'u8', 0, 'GET', 'u8', 1, 'GET', 'u8', 8));
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 3.2.0
     */
    public function testBitfieldWithSetModifier()
    {
        $redis = $this->getClient();

        $redis->setbit('string', 0, 1);
        $redis->setbit('string', 8, 1);

        $this->assertSame(array(128), $redis->bitfield('string', 'SET', 'u8', 0, 1));
        $this->assertSame(array(1, 128), $redis->bitfield('string', 'SET', 'u8', 0, 128, 'SET', 'u8', 8, 1));
        $this->assertSame(array(1, 128), $redis->bitfield('string', 'SET', 'u8', 8, 128, 'GET', 'u8', 8));

        $this->assertSame("\x80\x80", $redis->get('string'));
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 3.2.0
     */
    public function testBitfieldWithIncrbyModifier()
    {
        $redis = $this->getClient();

        $redis->setbit('string', 0, 1);
        $redis->setbit('string', 8, 1);

        $this->assertSame(array(138), $redis->bitfield('string', 'INCRBY', 'u8', 0, 10));
        $this->assertSame(array(143, 128), $redis->bitfield('string', 'INCRBY', 'u8', 0, 5, 'INCRBY', 'u8', 0, -15));

        $this->assertSame("\x80\x80", $redis->get('string'));
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 3.2.0
     * @expectedException \Predis\Response\ServerException
     * @expectedExceptionMessage Operation against a key holding the wrong kind of value
     */
    public function testThrowsExceptionOnWrongType()
    {
        $this->markTestSkipped('Currently skipped due issues in Redis (see antirez/redis#3259).');

        $redis = $this->getClient();

        $redis->lpush('metavars', 'foo');
        $redis->bitfield('metavars', 'SET', 'u4', '0', '1');
    }
}
