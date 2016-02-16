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
class ZSetUnionStoreTest extends PredisCommandTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getExpectedCommand()
    {
        return 'Predis\Command\ZSetUnionStore';
    }

    /**
     * {@inheritdoc}
     */
    protected function getExpectedId()
    {
        return 'ZUNIONSTORE';
    }

    /**
     * @group disconnected
     */
    public function testFilterArguments()
    {
        $modifiers = array(
            'aggregate' => 'sum',
            'weights' => array(10, 100),
        );
        $arguments = array('zset:destination', 2, 'zset1', 'zset2', $modifiers);

        $expected = array(
            'zset:destination', 2, 'zset1', 'zset2', 'WEIGHTS', 10, 100, 'AGGREGATE', 'sum',
        );

        $command = $this->getCommand();
        $command->setArguments($arguments);

        $this->assertSame($expected, $command->getArguments());
    }

    /**
     * @group disconnected
     */
    public function testFilterArgumentsSourceKeysAsSingleArray()
    {
        $modifiers = array(
            'aggregate' => 'sum',
            'weights' => array(10, 100),
        );
        $arguments = array('zset:destination', array('zset1', 'zset2'), $modifiers);

        $expected = array(
            'zset:destination', 2, 'zset1', 'zset2', 'WEIGHTS', 10, 100, 'AGGREGATE', 'sum',
        );

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
    public function testStoresUnionInNewSortedSet()
    {
        $redis = $this->getClient();

        $redis->zadd('letters:1st', 1, 'a', 2, 'b', 3, 'c');
        $redis->zadd('letters:2nd', 1, 'b', 2, 'c', 3, 'd');

        $this->assertSame(4, $redis->zunionstore('letters:out', 2, 'letters:1st', 'letters:2nd'));
        $this->assertSame(
            array('a' => '1', 'b' => '3', 'd' => '3', 'c' => '5'),
            $redis->zrange('letters:out', 0, -1, 'withscores')
        );

        $this->assertSame(3, $redis->zunionstore('letters:out', 2, 'letters:1st', 'letters:void'));
        $this->assertSame(3, $redis->zunionstore('letters:out', 2, 'letters:void', 'letters:2nd'));
        $this->assertSame(0, $redis->zunionstore('letters:out', 2, 'letters:void', 'letters:void'));
    }

    /**
     * @group connected
     */
    public function testStoresUnionWithAggregateModifier()
    {
        $redis = $this->getClient();

        $redis->zadd('letters:1st', 1, 'a', 2, 'b', 3, 'c');
        $redis->zadd('letters:2nd', 1, 'b', 2, 'c', 3, 'd');

        $options = array('aggregate' => 'min');
        $this->assertSame(4, $redis->zunionstore('letters:min', 2, 'letters:1st', 'letters:2nd', $options));
        $this->assertSame(
            array('a' => '1', 'b' => '1', 'c' => '2', 'd' => '3'),
            $redis->zrange('letters:min', 0, -1, 'withscores')
        );

        $options = array('aggregate' => 'max');
        $this->assertSame(4, $redis->zunionstore('letters:max', 2, 'letters:1st', 'letters:2nd', $options));
        $this->assertSame(
            array('a' => '1', 'b' => '2', 'c' => '3', 'd' => '3'),
            $redis->zrange('letters:max', 0, -1, 'withscores')
        );

        $options = array('aggregate' => 'sum');
        $this->assertSame(4, $redis->zunionstore('letters:sum', 2, 'letters:1st', 'letters:2nd', $options));
        $this->assertSame(
            array('a' => '1', 'b' => '3', 'd' => '3', 'c' => '5'),
            $redis->zrange('letters:sum', 0, -1, 'withscores')
        );
    }

    /**
     * @group connected
     */
    public function testStoresUnionWithWeightsModifier()
    {
        $redis = $this->getClient();

        $redis->zadd('letters:1st', 1, 'a', 2, 'b', 3, 'c');
        $redis->zadd('letters:2nd', 1, 'b', 2, 'c', 3, 'd');

        $options = array('weights' => array(2, 3));
        $this->assertSame(4, $redis->zunionstore('letters:out', 2, 'letters:1st', 'letters:2nd', $options));
        $this->assertSame(
            array('a' => '2', 'b' => '7', 'd' => '9', 'c' => '12'),
            $redis->zrange('letters:out', 0, -1, 'withscores')
        );
    }

    /**
     * @group connected
     */
    public function testStoresUnionWithCombinedModifiers()
    {
        $redis = $this->getClient();

        $redis->zadd('letters:1st', 1, 'a', 2, 'b', 3, 'c');
        $redis->zadd('letters:2nd', 1, 'b', 2, 'c', 3, 'd');

        $options = array('aggregate' => 'max', 'weights' => array(10, 15));
        $this->assertSame(4, $redis->zunionstore('letters:out', 2, 'letters:1st', 'letters:2nd', $options));
        $this->assertSame(
            array('a' => '10', 'b' => '20', 'c' => '30', 'd' => '45'),
            $redis->zrange('letters:out', 0, -1, 'withscores')
        );
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
        $redis->zunionstore('zset:destination', '1', 'foo');
    }
}
