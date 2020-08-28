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
 * @group realm-zset
 */
class ZINTERSTORE_Test extends PredisCommandTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getExpectedCommand(): string
    {
        return 'Predis\Command\Redis\ZINTERSTORE';
    }

    /**
     * {@inheritdoc}
     */
    protected function getExpectedId(): string
    {
        return 'ZINTERSTORE';
    }

    /**
     * @group disconnected
     */
    public function testFilterArguments(): void
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
    public function testFilterArgumentsSourceKeysAsSingleArray(): void
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
    public function testParseResponse(): void
    {
        $this->assertSame(1, $this->getCommand()->parseResponse(1));
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 2.0.0
     */
    public function testStoresIntersectionInNewSortedSet(): void
    {
        $redis = $this->getClient();

        $redis->zadd('letters:1st', 1, 'a', 2, 'b', 3, 'c');
        $redis->zadd('letters:2nd', 1, 'b', 2, 'c', 3, 'd');

        $this->assertSame(2, $redis->zinterstore('letters:out', 2, 'letters:1st', 'letters:2nd'));
        $this->assertSame(array('b' => '3', 'c' => '5'), $redis->zrange('letters:out', 0, -1, 'withscores'));

        $this->assertSame(0, $redis->zinterstore('letters:out', 2, 'letters:1st', 'letters:void'));
        $this->assertSame(0, $redis->zinterstore('letters:out', 2, 'letters:void', 'letters:2nd'));
        $this->assertSame(0, $redis->zinterstore('letters:out', 2, 'letters:void', 'letters:void'));
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 2.0.0
     */
    public function testStoresIntersectionWithAggregateModifier(): void
    {
        $redis = $this->getClient();

        $redis->zadd('letters:1st', 1, 'a', 2, 'b', 3, 'c');
        $redis->zadd('letters:2nd', 1, 'b', 2, 'c', 3, 'd');

        $options = array('aggregate' => 'min');
        $this->assertSame(2, $redis->zinterstore('letters:min', 2, 'letters:1st', 'letters:2nd', $options));
        $this->assertSame(array('b' => '1', 'c' => '2'), $redis->zrange('letters:min', 0, -1, 'withscores'));

        $options = array('aggregate' => 'max');
        $this->assertSame(2, $redis->zinterstore('letters:max', 2, 'letters:1st', 'letters:2nd', $options));
        $this->assertSame(array('b' => '2', 'c' => '3'), $redis->zrange('letters:max', 0, -1, 'withscores'));

        $options = array('aggregate' => 'sum');
        $this->assertSame(2, $redis->zinterstore('letters:sum', 2, 'letters:1st', 'letters:2nd', $options));
        $this->assertSame(array('b' => '3', 'c' => '5'), $redis->zrange('letters:sum', 0, -1, 'withscores'));
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 2.0.0
     */
    public function testStoresIntersectionWithWeightsModifier(): void
    {
        $redis = $this->getClient();

        $redis->zadd('letters:1st', 1, 'a', 2, 'b', 3, 'c');
        $redis->zadd('letters:2nd', 1, 'b', 2, 'c', 3, 'd');

        $options = array('weights' => array(2, 3));
        $this->assertSame(2, $redis->zinterstore('letters:out', 2, 'letters:1st', 'letters:2nd', $options));
        $this->assertSame(array('b' => '7', 'c' => '12'), $redis->zrange('letters:out', 0, -1, 'withscores'));
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 2.0.0
     */
    public function testStoresIntersectionWithCombinedModifiers(): void
    {
        $redis = $this->getClient();

        $redis->zadd('letters:1st', 1, 'a', 2, 'b', 3, 'c');
        $redis->zadd('letters:2nd', 1, 'b', 2, 'c', 3, 'd');

        $options = array('aggregate' => 'max', 'weights' => array(10, 15));
        $this->assertSame(2, $redis->zinterstore('letters:out', 2, 'letters:1st', 'letters:2nd', $options));
        $this->assertSame(array('b' => '20', 'c' => '30'), $redis->zrange('letters:out', 0, -1, 'withscores'));
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 2.0.0
     */
    public function testThrowsExceptionOnWrongType(): void
    {
        $this->expectException('Predis\Response\ServerException');
        $this->expectExceptionMessage('Operation against a key holding the wrong kind of value');

        $redis = $this->getClient();

        $redis->set('foo', 'bar');
        $redis->zinterstore('zset:destination', '1', 'foo');
    }
}
