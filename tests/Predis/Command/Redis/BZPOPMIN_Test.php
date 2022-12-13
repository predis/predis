<?php

namespace Predis\Command\Redis;

use Predis\Response\ServerException;
use UnexpectedValueException;

class BZPOPMIN_Test extends PredisCommandTestCase
{
    /**
     * @inheritDoc
     */
    protected function getExpectedCommand(): string
    {
        return BZPOPMIN::class;
    }

    /**
     * @inheritDoc
     */
    protected function getExpectedId(): string
    {
        return 'BZPOPMIN';
    }

    /**
     * @group connected
     * @return void
     * @requiresRedisVersion >= 5.0.0
     */
    public function testReturnsPoppedMinElementFromGivenNonEmptySortedSet(): void
    {
        $redis = $this->getClient();
        $sortedSetDictionary = [1, 'member1', 2, 'member2', 3, 'member3'];
        $expectedResponse = ['test-bzpopmin' => ['member1' => '1']];
        $expectedModifiedSortedSet = ['member2', 'member3'];

        $redis->zadd('test-bzpopmin', ...$sortedSetDictionary);

        $this->assertSame($expectedResponse, $redis->bzpopmin(['empty sorted set','test-bzpopmin'], 0));
        $this->assertSame($expectedModifiedSortedSet, $redis->zrange('test-bzpopmin', 0, -1));
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

        $redis->bzpopmin(1, 0);
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

        $redis->set('bzpopmin_foo', 'bar');
        $redis->bzpopmin(['bzpopmin_foo'], 0);
    }
}
