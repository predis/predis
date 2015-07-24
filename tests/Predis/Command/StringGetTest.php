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
class StringGetTest extends PredisCommandTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getExpectedCommand()
    {
        return 'Predis\Command\StringGet';
    }

    /**
     * {@inheritdoc}
     */
    protected function getExpectedId()
    {
        return 'GET';
    }

    /**
     * @group disconnected
     */
    public function testFilterArguments()
    {
        $arguments = array('foo');
        $expected = array('foo');

        $command = $this->getCommand();
        $command->setArguments($arguments);

        $this->assertSame($expected, $command->getArguments());
    }

    /**
     * @group disconnected
     */
    public function testParseResponse()
    {
        $this->assertSame('bar', $this->getCommand()->parseResponse('bar'));
    }

    /**
     * @group connected
     */
    public function testReturnsStringValue()
    {
        $redis = $this->getClient();

        $this->assertEquals('OK', $redis->set('foo', 'bar'));
        $this->assertEquals('bar', $redis->get('foo'));
    }

    /**
     * @group connected
     */
    public function testReturnsEmptyStringOnEmptyStrings()
    {
        $redis = $this->getClient();

        $redis->set('foo', '');

        $this->assertSame(1, $redis->exists('foo'));
        $this->assertSame('', $redis->get('foo'));
    }

    /**
     * @group connected
     */
    public function testReturnsNullOnNonExistingKeys()
    {
        $redis = $this->getClient();

        $this->assertSame(0, $redis->exists('foo'));
        $this->assertNull($redis->get('foo'));
    }

    /**
     * @group connected
     * @expectedException \Predis\Response\ServerException
     * @expectedExceptionMessage Operation against a key holding the wrong kind of value
     */
    public function testThrowsExceptionOnWrongType()
    {
        $redis = $this->getClient();

        $redis->rpush('metavars', 'foo');
        $redis->get('metavars');
    }
}
