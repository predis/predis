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
 * @group realm-stream
 */
class XLEN_Test extends PredisCommandTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getExpectedCommand(): string
    {
        return 'Predis\Command\Redis\XLEN';
    }

    /**
     * {@inheritdoc}
     */
    protected function getExpectedId(): string
    {
        return 'XLEN';
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
     * @requiresRedisVersion >= 5.0.0
     */
    public function testReturnsLengthOfList(): void
    {
        $redis = $this->getClient();

        $redis->xadd('stream', ['key' => 'val']);
        $redis->xadd('stream', ['key' => 'val']);
        $this->assertSame(2, $redis->xlen('stream'));

        $redis->xadd('stream', ['key' => 'val']);
        $this->assertSame(3, $redis->xlen('stream'));
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 5.0.0
     */
    public function testReturnsZeroLengthOnNonExistingList(): void
    {
        $redis = $this->getClient();

        $this->assertSame(0, $redis->llen('stream'));
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 5.0.0
     */
    public function testThrowsExceptionOnWrongType(): void
    {
        $this->expectException('Predis\Response\ServerException');
        $this->expectExceptionMessage('Operation against a key holding the wrong kind of value');

        $redis = $this->getClient();

        $redis->set('foo', 'bar');
        $redis->xlen('foo');
    }
}
