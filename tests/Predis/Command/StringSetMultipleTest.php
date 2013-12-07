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
class StringSetMultipleTest extends PredisCommandTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getExpectedCommand()
    {
        return 'Predis\Command\StringSetMultiple';
    }

    /**
     * {@inheritdoc}
     */
    protected function getExpectedId()
    {
        return 'MSET';
    }

    /**
     * @group disconnected
     */
    public function testFilterArguments()
    {
        $arguments = array('foo', 'bar', 'hoge', 'piyo');
        $expected = array('foo', 'bar', 'hoge', 'piyo');

        $command = $this->getCommand();
        $command->setArguments($arguments);

        $this->assertSame($expected, $command->getArguments());
    }

    /**
     * @group disconnected
     */
    public function testFilterArgumentsAsSingleNamedArray()
    {
        $arguments = array(array('foo' => 'bar', 'hoge' => 'piyo'));
        $expected = array('foo', 'bar', 'hoge', 'piyo');

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
    public function testCreatesMultipleKeys()
    {
        $redis = $this->getClient();

        $this->assertEquals('OK', $redis->mset('foo', 'bar', 'hoge', 'piyo'));
        $this->assertSame('bar', $redis->get('foo'));
        $this->assertSame('piyo', $redis->get('hoge'));
    }
}
