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
class StringSetMultiplePreserveTest extends PredisCommandTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getExpectedCommand()
    {
        return 'Predis\Command\StringSetMultiplePreserve';
    }

    /**
     * {@inheritdoc}
     */
    protected function getExpectedId()
    {
        return 'MSETNX';
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
        $this->assertSame(0, $this->getCommand()->parseResponse(0));
        $this->assertSame(1, $this->getCommand()->parseResponse(1));
    }

    /**
     * @group connected
     */
    public function testCreatesMultipleKeys()
    {
        $redis = $this->getClient();

        $this->assertSame(1, $redis->msetnx('foo', 'bar', 'hoge', 'piyo'));
        $this->assertSame('bar', $redis->get('foo'));
        $this->assertSame('piyo', $redis->get('hoge'));
    }

    /**
     * @group connected
     */
    public function testCreatesMultipleKeysAndPreservesExistingOnes()
    {
        $redis = $this->getClient();

        $redis->set('foo', 'bar');

        $this->assertSame(0, $redis->msetnx('foo', 'barbar', 'hoge', 'piyo'));
        $this->assertSame('bar', $redis->get('foo'));
        $this->assertSame(0, $redis->exists('hoge'));
    }
}
