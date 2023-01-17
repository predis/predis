<?php

/*
 * This file is part of the Predis package.
 *
 * (c) 2009-2020 Daniele Alessandri
 * (c) 2021-2023 Till Krüss
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Command\Redis;

/**
 * @group commands
 * @group realm-list
 */
class LLEN_Test extends PredisCommandTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getExpectedCommand(): string
    {
        return 'Predis\Command\Redis\LLEN';
    }

    /**
     * {@inheritdoc}
     */
    protected function getExpectedId(): string
    {
        return 'LLEN';
    }

    /**
     * @group disconnected
     */
    public function testFilterArguments(): void
    {
        $arguments = ['key'];
        $expected = ['key'];

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
     * @requiresRedisVersion >= 2.2.0
     */
    public function testReturnsLengthOfList(): void
    {
        $redis = $this->getClient();

        $redis->rpush('letters', 'a', 'b', 'c');
        $this->assertSame(3, $redis->llen('letters'));

        $redis->rpush('letters', 'd', 'e', 'f');
        $this->assertSame(6, $redis->llen('letters'));
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 2.2.0
     */
    public function testReturnsZeroLengthOnNonExistingList(): void
    {
        $redis = $this->getClient();

        $this->assertSame(0, $redis->llen('letters'));
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 2.2.0
     */
    public function testThrowsExceptionOnWrongType(): void
    {
        $this->expectException('Predis\Response\ServerException');
        $this->expectExceptionMessage('Operation against a key holding the wrong kind of value');

        $redis = $this->getClient();

        $redis->set('foo', 'bar');
        $redis->llen('foo');
    }
}
