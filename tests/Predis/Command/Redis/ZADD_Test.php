<?php

/*
 * This file is part of the Predis package.
 *
 * (c) 2009-2020 Daniele Alessandri
 * (c) 2021-2024 Till Krüss
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Command\Redis;

/**
 * @group commands
 * @group realm-zset
 */
class ZADD_Test extends PredisCommandTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getExpectedCommand(): string
    {
        return 'Predis\Command\Redis\ZADD';
    }

    /**
     * {@inheritdoc}
     */
    protected function getExpectedId(): string
    {
        return 'ZADD';
    }

    /**
     * @group disconnected
     */
    public function testFilterArguments(): void
    {
        $arguments = ['key', 1, 'member1', 2, 'member2'];
        $expected = ['key', 1, 'member1', 2, 'member2'];

        $command = $this->getCommand();
        $command->setArguments($arguments);

        $this->assertSame($expected, $command->getArguments());
    }

    /**
     * @group disconnected
     */
    public function testFilterArgumentsMembersScoresAsSingleArray(): void
    {
        $arguments = ['key', ['member1' => 1, 'member2' => 2]];
        $expected = ['key', 1, 'member1', 2, 'member2'];

        $command = $this->getCommand();
        $command->setArguments($arguments);

        $this->assertSame($expected, $command->getArguments());
    }

    /**
     * @group disconnected
     */
    public function testFilterArgumentsMembersScoresAsSingleArrayWithModifiers(): void
    {
        $arguments = ['key', 'NX', 'CH', ['member1' => 1, 'member2' => 2]];
        $expected = ['key', 'NX', 'CH', 1, 'member1', 2, 'member2'];

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
     */
    public function testAddsOrUpdatesMembersOrderingByScore(): void
    {
        $redis = $this->getClient();

        $this->assertSame(5, $redis->zadd('letters', 1, 'a', 2, 'b', 3, 'c', 4, 'd', 5, 'e'));
        $this->assertSame(['a', 'b', 'c', 'd', 'e'], $redis->zrange('letters', 0, -1));

        $this->assertSame(1, $redis->zadd('letters', 1, 'e', 8, 'c', 6, 'f'));
        $this->assertSame(['a', 'e', 'b', 'd', 'f', 'c'], $redis->zrange('letters', 0, -1));
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 3.0.2
     */
    public function testOnlyAddsNonExistingMembersWithModifierNX(): void
    {
        $redis = $this->getClient();

        $this->assertSame(5, $redis->zadd('letters', 1, 'a', 2, 'b', 3, 'c', 4, 'd', 5, 'e'));
        $this->assertSame(['a', 'b', 'c', 'd', 'e'], $redis->zrange('letters', 0, -1));

        $this->assertSame(2, $redis->zadd('letters', 'NX', 8, 'a', 1, 'f', 8, 'g', 4, 'e'));
        $this->assertSame(['a', 'f', 'b', 'c', 'd', 'e', 'g'], $redis->zrange('letters', 0, -1));
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 3.0.2
     */
    public function testOnlyUpdatesExistingMembersWithModifierXX(): void
    {
        $redis = $this->getClient();

        $this->assertSame(5, $redis->zadd('letters', 1, 'a', 2, 'b', 3, 'c', 4, 'd', 5, 'e'));
        $this->assertSame(['a', 'b', 'c', 'd', 'e'], $redis->zrange('letters', 0, -1));

        $this->assertSame(0, $redis->zadd('letters', 'XX', 1, 'd', 2, 'c', 3, 'b', 1, 'x', 0, 'y'));
        $this->assertSame(['a', 'd', 'c', 'b', 'e'], $redis->zrange('letters', 0, -1));
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 3.0.2
     */
    public function testReturnsNumberOfAddedAndUpdatedElementsWithModifierCH(): void
    {
        $redis = $this->getClient();

        $this->assertSame(5, $redis->zadd('letters', 'CH', 1, 'a', 2, 'b', 3, 'c', 4, 'd', 5, 'e'));
        $this->assertSame(['a', 'b', 'c', 'd', 'e'], $redis->zrange('letters', 0, -1));

        $this->assertSame(2, $redis->zadd('letters', 'NX', 'CH', 8, 'a', 1, 'f', 8, 'g', 4, 'e'));
        $this->assertSame(['a', 'f', 'b', 'c', 'd', 'e', 'g'], $redis->zrange('letters', 0, -1));

        $this->assertSame(3, $redis->zadd('letters', 'XX', 'CH', 1, 'd', 2, 'c', 3, 'b', 1, 'x', 0, 'y'));
        $this->assertSame(['a', 'd', 'f', 'c', 'b', 'e', 'g'], $redis->zrange('letters', 0, -1));
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 3.0.2
     */
    public function testActsLikeZINCRBYWithModifierINCR(): void
    {
        $redis = $this->getClient();

        $this->assertEquals('1', $redis->zadd('letters', 'INCR', 1, 'a'));
        $this->assertEquals('0', $redis->zadd('letters', 'INCR', -1, 'a'));
        $this->assertEquals('0.5', $redis->zadd('letters', 'INCR', 0.5, 'a'));
        $this->assertEquals('-10', $redis->zadd('letters', 'INCR', -10.5, 'a'));
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 3.0.2
     */
    public function testDoesNotAcceptMultipleScoreElementPairsWithModifierINCR(): void
    {
        $this->expectException('Predis\Response\ServerException');
        $this->expectExceptionMessage('INCR option supports a single increment-element pair');

        $redis = $this->getClient();

        $redis->zadd('letters', 'INCR', 1, 'a', 2, 'b');
    }

    /**
     * @group connected
     */
    public function testAcceptsFloatValuesAsScore(): void
    {
        $redis = $this->getClient();

        $redis->zadd('letters', 0.2, 'b', 0.3, 'a', 0.1, 'c');
        $this->assertSame(['c', 'b', 'a'], $redis->zrange('letters', 0, -1));
    }

    /**
     * @group connected
     */
    public function testThrowsExceptionOnWrongType(): void
    {
        $this->expectException('Predis\Response\ServerException');
        $this->expectExceptionMessage('Operation against a key holding the wrong kind of value');

        $redis = $this->getClient();

        $redis->set('foo', 'bar');
        $redis->zadd('foo', 10, 'bar');
    }
}
