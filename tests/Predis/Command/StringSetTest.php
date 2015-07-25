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
class StringSetTest extends PredisCommandTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getExpectedCommand()
    {
        return 'Predis\Command\StringSet';
    }

    /**
     * {@inheritdoc}
     */
    protected function getExpectedId()
    {
        return 'SET';
    }

    /**
     * @group disconnected
     */
    public function testFilterArguments()
    {
        $arguments = array('foo', 'bar');
        $expected = array('foo', 'bar');

        $command = $this->getCommand();
        $command->setArguments($arguments);

        $this->assertSame($expected, $command->getArguments());
    }

    /**
     * @group disconnected
     */
    public function testFilterArgumentsRedisWithModifiers()
    {
        $arguments = array('foo', 'bar', 'EX', '10', 'NX');
        $expected = array('foo', 'bar', 'EX', '10', 'NX');

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
    public function testSetStringValue()
    {
        $redis = $this->getClient();

        $this->assertEquals('OK', $redis->set('foo', 'bar'));
        $this->assertSame(1, $redis->exists('foo'));
        $this->assertSame('bar', $redis->get('foo'));
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 2.6.12
     */
    public function testSetStringValueWithModifierEX()
    {
        $redis = $this->getClient();

        $this->assertEquals('OK', $redis->set('foo', 'bar', 'ex', 1));
        $this->assertSame(1, $redis->ttl('foo'));
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 2.6.12
     */
    public function testSetStringValueWithModifierPX()
    {
        $redis = $this->getClient();

        $this->assertEquals('OK', $redis->set('foo', 'bar', 'px', 1000));

        $pttl = $redis->pttl('foo');
        $this->assertGreaterThan(0, $pttl);
        $this->assertLessThanOrEqual(1000, $pttl);
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 2.6.12
     */
    public function testSetStringValueWithModifierNX()
    {
        $redis = $this->getClient();

        $this->assertEquals('OK', $redis->set('foo', 'bar', 'NX'));
        $this->assertNull($redis->set('foo', 'bar', 'NX'));
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 2.6.12
     */
    public function testSetStringValueWithModifierXX()
    {
        $redis = $this->getClient();

        $this->assertEquals('OK', $redis->set('foo', 'bar'));

        $this->assertEquals('OK', $redis->set('foo', 'barbar', 'XX'));
        $this->assertNull($redis->set('foofoo', 'barbar', 'XX'));
    }
}
