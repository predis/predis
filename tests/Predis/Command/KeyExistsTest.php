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
 * @group realm-key
 */
class KeyExistsTest extends PredisCommandTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getExpectedCommand()
    {
        return 'Predis\Command\KeyExists';
    }

    /**
     * {@inheritdoc}
     */
    protected function getExpectedId()
    {
        return 'EXISTS';
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
    public function testFilterArgumentsMultipleKeys()
    {
        $arguments = array('key:1', 'key:2', 'key:3');
        $expected = array('key:1', 'key:2', 'key:3');

        $command = $this->getCommand();
        $command->setArguments($arguments);

        $this->assertSame($expected, $command->getArguments());
    }

    /**
     * @group disconnected
     */
    public function testParseResponse()
    {
        $command = $this->getCommand();

        $this->assertSame(0, $command->parseResponse(0));
        $this->assertSame(1, $command->parseResponse(1));
        $this->assertSame(2, $command->parseResponse(2));
    }

    /**
     * @group connected
     */
    public function testReturnValueWhenKeyExists()
    {
        $redis = $this->getClient();

        $redis->set('foo', 'bar');
        $this->assertSame(1, $redis->exists('foo'));
    }

    /**
     * @group connected
     */
    public function testReturnValueWhenKeyDoesNotExist()
    {
        $redis = $this->getClient();

        $this->assertSame(0, $redis->exists('foo'));
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 3.0.3
     */
    public function testReturnValueWhenKeysExist()
    {
        $redis = $this->getClient();

        $redis->mset('foo', 'bar', 'hoge', 'piyo');
        $this->assertSame(2, $redis->exists('foo', 'hoge'));
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 3.0.3
     */
    public function testReturnValueWhenKeyDoNotExist()
    {
        $redis = $this->getClient();

        $this->assertSame(0, $redis->exists('foo', 'bar'));
    }
}
