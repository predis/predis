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
 * @group realm-set
 */
class SetMoveTest extends PredisCommandTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getExpectedCommand()
    {
        return 'Predis\Command\SetMove';
    }

    /**
     * {@inheritdoc}
     */
    protected function getExpectedId()
    {
        return 'SMOVE';
    }

    /**
     * @group disconnected
     */
    public function testFilterArguments()
    {
        $arguments = array('key:source', 'key:destination', 'member');
        $expected = array('key:source', 'key:destination', 'member');

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
    }

    /**
     * @group connected
     */
    public function testReturnsMemberExistenceInSet()
    {
        $redis = $this->getClient();

        $redis->sadd('letters:source', 'a', 'b', 'c');

        $this->assertSame(1, $redis->smove('letters:source', 'letters:destination', 'b'));
        $this->assertSame(0, $redis->smove('letters:source', 'letters:destination', 'z'));

        $this->assertSameValues(array('a', 'c'), $redis->smembers('letters:source'));
        $this->assertSameValues(array('b'), $redis->smembers('letters:destination'));
    }

    /**
     * @group connected
     * @expectedException \Predis\Response\ServerException
     * @expectedExceptionMessage Operation against a key holding the wrong kind of value
     */
    public function testThrowsExceptionOnWrongTypeOfSourceKey()
    {
        $redis = $this->getClient();

        $redis->set('set:source', 'foo');
        $redis->sadd('set:destination', 'bar');
        $redis->smove('set:destination', 'set:source', 'foo');
    }

    /**
     * @group connected
     * @expectedException \Predis\Response\ServerException
     * @expectedExceptionMessage Operation against a key holding the wrong kind of value
     */
    public function testThrowsExceptionOnWrongTypeOfDestinationKey()
    {
        $redis = $this->getClient();

        $redis->sadd('set:source', 'foo');
        $redis->set('set:destination', 'bar');
        $redis->smove('set:destination', 'set:source', 'foo');
    }
}
