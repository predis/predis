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
class SetMembersTest extends PredisCommandTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getExpectedCommand()
    {
        return 'Predis\Command\SetMembers';
    }

    /**
     * {@inheritdoc}
     */
    protected function getExpectedId()
    {
        return 'SMEMBERS';
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
        $raw = array('member1', 'member2', 'member3');
        $expected = array('member1', 'member2', 'member3');

        $command = $this->getCommand();

        $this->assertSame($expected, $command->parseResponse($raw));
    }

    /**
     * @group connected
     */
    public function testReturnsEmptyArrayOnNonExistingSet()
    {
        $redis = $this->getClient();

        $redis->sadd('letters', 'a', 'b', 'c', 'd', 'e');

        $this->assertSameValues(array('a', 'b', 'c', 'd', 'e'), $redis->smembers('letters'));
        $this->assertSame(array(), $redis->smembers('digits'));
    }

    /**
     * @group connected
     * @expectedException \Predis\Response\ServerException
     * @expectedExceptionMessage Operation against a key holding the wrong kind of value
     */
    public function testThrowsExceptionOnWrongType()
    {
        $redis = $this->getClient();

        $redis->set('foo', 'bar');
        $redis->smembers('foo');
    }
}
