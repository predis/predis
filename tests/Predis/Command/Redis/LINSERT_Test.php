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
 * @group realm-list
 */
class LINSERT_Test extends PredisCommandTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getExpectedCommand(): string
    {
        return 'Predis\Command\Redis\LINSERT';
    }

    /**
     * {@inheritdoc}
     */
    protected function getExpectedId(): string
    {
        return 'LINSERT';
    }

    /**
     * @group disconnected
     */
    public function testFilterArguments(): void
    {
        $arguments = array('key', 'before', 'value1', 'value2');
        $expected = array('key', 'before', 'value1', 'value2');

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
    public function testReturnsLengthOfListAfterInser(): void
    {
        $redis = $this->getClient();

        $redis->rpush('letters', 'a', 'c', 'e');

        $this->assertSame(4, $redis->linsert('letters', 'before', 'c', 'b'));
        $this->assertSame(5, $redis->linsert('letters', 'after', 'c', 'd'));
        $this->assertSame(array('a', 'b', 'c', 'd', 'e'), $redis->lrange('letters', 0, -1));
    }

    /**
     * @group connected
     */
    public function testReturnsNegativeLengthOnFailedInsert(): void
    {
        $redis = $this->getClient();

        $redis->rpush('letters', 'a', 'c', 'e');

        $this->assertSame(-1, $redis->linsert('letters', 'before', 'n', 'm'));
        $this->assertSame(-1, $redis->linsert('letters', 'after', 'o', 'p'));
    }

    /**
     * @group connected
     */
    public function testReturnsZeroLengthOnNonExistingList(): void
    {
        $redis = $this->getClient();

        $this->assertSame(0, $redis->linsert('letters', 'after', 'a', 'b'));
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
        $redis->linsert('foo', 'BEFORE', 'bar', 'baz');
    }
}
