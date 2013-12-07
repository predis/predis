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
        $this->assertTrue($redis->exists('foo'));
        $this->assertSame('bar', $redis->get('foo'));
    }
}
