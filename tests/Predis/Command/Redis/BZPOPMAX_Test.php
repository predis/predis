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

use Predis\Response\ServerException;
use UnexpectedValueException;

class BZPOPMAX_Test extends PredisCommandTestCase
{
    /**
     * {@inheritDoc}
     */
    protected function getExpectedCommand(): string
    {
        return BZPOPMAX::class;
    }

    /**
     * {@inheritDoc}
     */
    protected function getExpectedId(): string
    {
        return 'BZPOPMAX';
    }

    /**
     * @group connected
     * @return void
     * @requiresRedisVersion >= 5.0.0
     */
    public function testReturnsPoppedMaxElementFromGivenNonEmptySortedSet(): void
    {
        $redis = $this->getClient();
        $sortedSetDictionary = [1, 'member1', 2, 'member2', 3, 'member3'];
        $expectedResponse = ['test-bzpopmax' => ['member3' => '3']];
        $expectedModifiedSortedSet = ['member1', 'member2'];

        $redis->zadd('test-bzpopmax', ...$sortedSetDictionary);

        $this->assertEquals($expectedResponse, $redis->bzpopmax(['empty sorted set', 'test-bzpopmax'], 0));
        $this->assertSame($expectedModifiedSortedSet, $redis->zrange('test-bzpopmax', 0, -1));
    }

    /**
     * @group connected
     * @return void
     * @requiresRedisVersion >= 5.0.0
     */
    public function testThrowsExceptionOnUnexpectedValueGiven(): void
    {
        $redis = $this->getClient();

        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('Wrong keys argument type or position offset');

        $redis->bzpopmax(1, 0);
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 5.0.0
     */
    public function testThrowsExceptionOnWrongType(): void
    {
        $this->expectException(ServerException::class);
        $this->expectExceptionMessage('Operation against a key holding the wrong kind of value');

        $redis = $this->getClient();

        $redis->set('bzpopmax_foo', 'bar');
        $redis->bzpopmax(['bzpopmax_foo'], 0);
    }
}
