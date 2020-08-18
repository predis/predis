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
class DECR_Test extends PredisCommandTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getExpectedCommand()
    {
        return 'Predis\Command\Redis\DECR';
    }

    /**
     * {@inheritdoc}
     */
    protected function getExpectedId()
    {
        return 'DECR';
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
    public function testParseResponse()
    {
        $this->assertSame(5, $this->getCommand()->parseResponse(5));
    }

    /**
     * @group connected
     */
    public function testCreatesNewKeyOnNonExistingKey()
    {
        $redis = $this->getClient();

        $this->assertSame(-1, $redis->decr('foo'));
        $this->assertEquals(-1, $redis->get('foo'));
    }

    /**
     * @group connected
     */
    public function testReturnsTheValueOfTheKeyAfterDecrement()
    {
        $redis = $this->getClient();

        $redis->set('foo', 1);

        $this->assertSame(0, $redis->decr('foo'));
        $this->assertSame(-1, $redis->decr('foo'));
    }

    /**
     * @group connected
     */
    public function testThrowsExceptionOnKeyValueNotInteger()
    {
        $this->expectException('Predis\Response\ServerException');
        $this->expectExceptionMessage('ERR value is not an integer or out of range');

        $redis = $this->getClient();

        $redis->set('foo', 'bar');
        $redis->decr('foo');
    }

    /**
     * @group connected
     */
    public function testThrowsExceptionOnWrongType()
    {
        $this->expectException('Predis\Response\ServerException');
        $this->expectExceptionMessage('Operation against a key holding the wrong kind of value');

        $redis = $this->getClient();

        $redis->lpush('metavars', 'foo');
        $redis->decr('metavars');
    }
}
