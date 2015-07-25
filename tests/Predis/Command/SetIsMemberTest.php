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
class SetIsMemberTest extends PredisCommandTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getExpectedCommand()
    {
        return 'Predis\Command\SetIsMember';
    }

    /**
     * {@inheritdoc}
     */
    protected function getExpectedId()
    {
        return 'SISMEMBER';
    }

    /**
     * @group disconnected
     */
    public function testFilterArguments()
    {
        $arguments = array('key', 'member');
        $expected = array('key', 'member');

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

        $redis->sadd('letters', 'a', 'b', 'c');

        $this->assertSame(1, $redis->sismember('letters', 'a'));
        $this->assertSame(0, $redis->sismember('letters', 'z'));
    }

    /**
     * @group connected
     */
    public function testReturnsZeroOnNonExistingSet()
    {
        $redis = $this->getClient();

        $this->assertSame(0, $redis->sismember('letters', 'a'));
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
        $redis->sismember('foo', 'bar');
    }
}
