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
class SetPopTest extends PredisCommandTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getExpectedCommand()
    {
        return 'Predis\Command\SetPop';
    }

    /**
     * {@inheritdoc}
     */
    protected function getExpectedId()
    {
        return 'SPOP';
    }

    /**
     * @group disconnected
     */
    public function testFilterArguments()
    {
        $arguments = array('key', 2);
        $expected = array('key', 2);

        $command = $this->getCommand();
        $command->setArguments($arguments);

        $this->assertSame($expected, $command->getArguments());
    }

    /**
     * @group disconnected
     */
    public function testParseResponse()
    {
        $this->assertSame('member', $this->getCommand()->parseResponse('member'));
    }

    /**
     * @group connected
     */
    public function testPopsRandomMemberFromSet()
    {
        $redis = $this->getClient();

        $redis->sadd('letters', 'a', 'b');

        $this->assertContains($redis->spop('letters'), array('a', 'b'));
        $this->assertContains($redis->spop('letters'), array('a', 'b'));

        $this->assertNull($redis->spop('letters'));
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 3.2.0
     */
    public function testPopsMoreRandomMembersFromSet()
    {
        $redis = $this->getClient();

        $redis->sadd('letters', 'a', 'b', 'c');

        $this->assertSameValues(array('a', 'b', 'c'), $redis->spop('letters', 3));
        $this->assertEmpty($redis->spop('letters', 3));

        $this->assertNull($redis->spop('letters'));
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
        $redis->spop('foo');
    }
}
