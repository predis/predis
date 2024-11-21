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
 * @group realm-set
 */
class SREM_Test extends PredisCommandTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getExpectedCommand(): string
    {
        return 'Predis\Command\Redis\SREM';
    }

    /**
     * {@inheritdoc}
     */
    protected function getExpectedId(): string
    {
        return 'SREM';
    }

    /**
     * @group disconnected
     */
    public function testFilterArguments(): void
    {
        $arguments = ['key', 'member1', 'member2', 'member3'];
        $expected = ['key', 'member1', 'member2', 'member3'];

        $command = $this->getCommand();
        $command->setArguments($arguments);

        $this->assertSame($expected, $command->getArguments());
    }

    /**
     * @group disconnected
     */
    public function testFilterArgumentsMembersAsSingleArray(): void
    {
        $arguments = ['key', ['member1', 'member2', 'member3']];
        $expected = ['key', 'member1', 'member2', 'member3'];

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
    public function testRemovesMembersFromSet(): void
    {
        $redis = $this->getClient();

        $redis->sadd('letters', 'a', 'b', 'c', 'd');

        $this->assertSame(1, $redis->srem('letters', 'b'));
        $this->assertSame(1, $redis->srem('letters', 'd', 'z'));
        $this->assertSameValues(['a', 'c'], $redis->smembers('letters'));

        $this->assertSame(0, $redis->srem('digits', 1));
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 2.4.0
     */
    public function testRemovesMembersFromSetVariadic(): void
    {
        $redis = $this->getClient();

        $redis->sadd('letters', 'a', 'b', 'c', 'd');

        $this->assertSame(2, $redis->srem('letters', 'b', 'd', 'z'));
        $this->assertSameValues(['a', 'c'], $redis->smembers('letters'));

        $this->assertSame(0, $redis->srem('digits', 1));
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 2.4.0
     */
    public function testRemovesMembersInArrayTypeFromSetVariadic(): void
    {
        $redis = $this->getClient();

        $redis->sadd('letters', 'a', 'b', 'c', 'd');

        $this->assertSame(2, $redis->srem('letters', ['b', 'd', 'z']));
        $this->assertSameValues(['a', 'c'], $redis->smembers('letters'));

        $this->assertSame(0, $redis->srem('digits', [1]));
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
        $redis->srem('foo', 'bar');
    }
}
