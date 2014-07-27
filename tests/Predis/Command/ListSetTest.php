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
 * @group realm-list
 */
class ListSetTest extends PredisCommandTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getExpectedCommand()
    {
        return 'Predis\Command\ListSet';
    }

    /**
     * {@inheritdoc}
     */
    protected function getExpectedId()
    {
        return 'LSET';
    }

    /**
     * @group disconnected
     */
    public function testFilterArguments()
    {
        $arguments = array('key', 0, 'value');
        $expected = array('key', 0, 'value');

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
    public function testSetsElementAtSpecifiedIndex()
    {
        $redis = $this->getClient();

        $redis->rpush('letters', 'a', 'b', 'c');

        $this->assertEquals('OK', $redis->lset('letters', 1, 'B'));
        $this->assertSame(array('a', 'B', 'c'), $redis->lrange('letters', 0, -1));
    }

    /**
     * @group connected
     * @expectedException \Predis\Response\ServerException
     * @expectedExceptionMessage ERR index out of range
     */
    public function testThrowsExceptionOnIndexOutOfRange()
    {
        $redis = $this->getClient();

        $redis->rpush('letters', 'a', 'b', 'c');
        $redis->lset('letters', 21, 'z');
    }

    /**
     * @group connected
     * @expectedException \Predis\Response\ServerException
     * @expectedExceptionMessage Operation against a key holding the wrong kind of value
     */
    public function testThrowsExceptionOnWrongType()
    {
        $redis = $this->getClient();

        $redis->set('metavars', 'foo');
        $redis->lset('metavars', 0, 'hoge');
    }
}
