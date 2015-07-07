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
 * @group realm-zset
 */
class ZSetAddTest extends PredisCommandTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getExpectedCommand()
    {
        return 'Predis\Command\ZSetAdd';
    }

    /**
     * {@inheritdoc}
     */
    protected function getExpectedId()
    {
        return 'ZADD';
    }

    /**
     * @group disconnected
     */
    public function testFilterArguments()
    {
        $arguments = array('key', 1, 'member1', 2, 'member2');
        $expected = array('key', 1, 'member1', 2, 'member2');

        $command = $this->getCommand();
        $command->setArguments($arguments);

        $this->assertSame($expected, $command->getArguments());
    }

    /**
     * @group disconnected
     */
    public function testFilterArgumentsMembersScoresAsSingleArray()
    {
        $arguments = array('key', array('member1' => 1, 'member2' => 2));
        $expected = array('key', 1, 'member1', 2, 'member2');

        $command = $this->getCommand();
        $command->setArguments($arguments);

        $this->assertSame($expected, $command->getArguments());
    }

    /**
     * @group disconnected
     */
    public function testFilterArgumentsMembersScoresAsSingleArrayWithModifiers()
    {
        $arguments = array('key', 'NX', 'CH', array('member1' => 1, 'member2' => 2));
        $expected = array('key', 'NX', 'CH', 1, 'member1', 2, 'member2');

        $command = $this->getCommand();
        $command->setArguments($arguments);

        $this->assertSame($expected, $command->getArguments());
    }

    /**
     * @group disconnected
     */
    public function testParseResponse()
    {
        $this->assertSame(1, $this->getCommand()->parseResponse(1));
    }

    /**
     * @group connected
     */
    public function testAddsOrUpdatesMembersOrderingByScore()
    {
        $redis = $this->getClient();

        $this->assertSame(5, $redis->zadd('letters', 1, 'a', 2, 'b', 3, 'c', 4, 'd', 5, 'e'));
        $this->assertSame(array('a', 'b', 'c', 'd', 'e'), $redis->zrange('letters', 0, -1));

        $this->assertSame(1, $redis->zadd('letters', 1, 'e', 8, 'c', 6, 'f'));
        $this->assertSame(array('a', 'e', 'b', 'd', 'f', 'c'), $redis->zrange('letters', 0, -1));
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 3.0.2
     */
    public function testOnlyAddsNonExistingMembersWithModifierNX()
    {
        $redis = $this->getClient();

        $this->assertSame(5, $redis->zadd('letters', 1, 'a', 2, 'b', 3, 'c', 4, 'd', 5, 'e'));
        $this->assertSame(array('a', 'b', 'c', 'd', 'e'), $redis->zrange('letters', 0, -1));

        $this->assertSame(2, $redis->zadd('letters', 'NX', 8, 'a', 1, 'f', 8, 'g', 4, 'e'));
        $this->assertSame(array('a', 'f', 'b', 'c', 'd', 'e', 'g'), $redis->zrange('letters', 0, -1));
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 3.0.2
     */
    public function testOnlyUpdatesExistingMembersWithModifierXX()
    {
        $redis = $this->getClient();

        $this->assertSame(5, $redis->zadd('letters', 1, 'a', 2, 'b', 3, 'c', 4, 'd', 5, 'e'));
        $this->assertSame(array('a', 'b', 'c', 'd', 'e'), $redis->zrange('letters', 0, -1));

        $this->assertSame(0, $redis->zadd('letters', 'XX', 1, 'd', 2, 'c', 3, 'b', 1, 'x', 0, 'y'));
        $this->assertSame(array('a', 'd', 'c', 'b', 'e'), $redis->zrange('letters', 0, -1));
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 3.0.2
     */
    public function testReturnsNumberOfAddedAndUpdatedElementsWithModifierCH()
    {
        $redis = $this->getClient();

        $this->assertSame(5, $redis->zadd('letters', 'CH', 1, 'a', 2, 'b', 3, 'c', 4, 'd', 5, 'e'));
        $this->assertSame(array('a', 'b', 'c', 'd', 'e'), $redis->zrange('letters', 0, -1));

        $this->assertSame(2, $redis->zadd('letters', 'NX', 'CH', 8, 'a', 1, 'f', 8, 'g', 4, 'e'));
        $this->assertSame(array('a', 'f', 'b', 'c', 'd', 'e', 'g'), $redis->zrange('letters', 0, -1));

        $this->assertSame(3, $redis->zadd('letters', 'XX', 'CH', 1, 'd', 2, 'c', 3, 'b', 1, 'x', 0, 'y'));
        $this->assertSame(array('a', 'd', 'f', 'c', 'b', 'e', 'g'), $redis->zrange('letters', 0, -1));
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 3.0.2
     */
    public function testActsLikeZINCRBYWithModifierINCR()
    {
        $redis = $this->getClient();

        $this->assertSame('1', $redis->zadd('letters', 'INCR', 1, 'a'));
        $this->assertSame('0', $redis->zadd('letters', 'INCR', -1, 'a'));
        $this->assertSame('0.5', $redis->zadd('letters', 'INCR', 0.5, 'a'));
        $this->assertSame('-10', $redis->zadd('letters', 'INCR', -10.5, 'a'));
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 3.0.2
     * @expectedException \Predis\Response\ServerException
     * @expectedExceptionMessage INCR option supports a single increment-element pair
     */
    public function testDoesNotAcceptMultipleScoreElementPairsWithModifierINCR()
    {
        $redis = $this->getClient();

        $redis->zadd('letters', 'INCR', 1, 'a', 2, 'b');
    }

    /**
     * @group connected
     */
    public function testAcceptsFloatValuesAsScore()
    {
        $redis = $this->getClient();

        $redis->zadd('letters', 0.2, 'b', 0.3, 'a', 0.1, 'c');
        $this->assertSame(array('c', 'b', 'a'), $redis->zrange('letters', 0, -1));
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
        $redis->zadd('foo', 10, 'bar');
    }
}
