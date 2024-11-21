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
 * @group realm-list
 */
class LRANGE_Test extends PredisCommandTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getExpectedCommand(): string
    {
        return 'Predis\Command\Redis\LRANGE';
    }

    /**
     * {@inheritdoc}
     */
    protected function getExpectedId(): string
    {
        return 'LRANGE';
    }

    /**
     * @group disconnected
     */
    public function testFilterArguments(): void
    {
        $arguments = ['key', 0, -1];
        $expected = ['key', 0, -1];

        $command = $this->getCommand();
        $command->setArguments($arguments);

        $this->assertSame($expected, $command->getArguments());
    }

    /**
     * @group disconnected
     */
    public function testParseResponse(): void
    {
        $raw = ['value1', 'value2', 'value3'];
        $expected = ['value1', 'value2', 'value3'];

        $command = $this->getCommand();

        $this->assertSame($expected, $command->parseResponse($raw));
    }

    /**
     * @group connected
     */
    public function testReturnsListSliceWithPositiveStartAndStop(): void
    {
        $redis = $this->getClient();

        $redis->rpush('letters', 'a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'l');

        $this->assertSame(['a', 'b', 'c', 'd'], $redis->lrange('letters', 0, 3));
        $this->assertSame(['e', 'f', 'g', 'h'], $redis->lrange('letters', 4, 7));
        $this->assertSame(['a', 'b'], $redis->lrange('letters', 0, 1));
        $this->assertSame(['a'], $redis->lrange('letters', 0, 0));
    }

    /**
     * @group connected
     */
    public function testReturnsListSliceWithPositiveStartAndNegativeStop(): void
    {
        $redis = $this->getClient();

        $redis->rpush('letters', 'a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'l');

        $this->assertSame(['a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'l'], $redis->lrange('letters', 0, -1));
        $this->assertSame(['f'], $redis->lrange('letters', 5, -5));
        $this->assertSame([], $redis->lrange('letters', 7, -5));
    }

    /**
     * @group connected
     */
    public function testReturnsListSliceWithNegativeStartAndStop(): void
    {
        $redis = $this->getClient();

        $redis->rpush('letters', 'a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'l');

        $this->assertSame(['f'], $redis->lrange('letters', -5, -5));
    }

    /**
     * @group connected
     */
    public function testHandlesStartAndStopOverflow(): void
    {
        $redis = $this->getClient();

        $redis->rpush('letters', 'a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'l');

        $this->assertSame(['a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'l'], $redis->lrange('letters', -100, 100));
    }

    /**
     * @group connected
     */
    public function testReturnsEmptyArrayOnNonExistingList(): void
    {
        $redis = $this->getClient();

        $this->assertSame([], $redis->lrange('letters', 0, -1));
    }

    /**
     * @group connected
     */
    public function testThrowsExceptionOnWrongType(): void
    {
        $this->expectException('Predis\Response\ServerException');
        $this->expectExceptionMessage('Operation against a key holding the wrong kind of value');

        $redis = $this->getClient();

        $redis->set('metavars', 'foo');
        $redis->lrange('metavars', 0, -1);
    }
}
