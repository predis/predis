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
class XDEL_Test extends PredisCommandTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getExpectedCommand(): string
    {
        return 'Predis\Command\Redis\XDEL';
    }

    /**
     * {@inheritdoc}
     */
    protected function getExpectedId(): string
    {
        return 'XDEL';
    }

    /**
     * @group disconnected
     */
    public function testFilterArguments(): void
    {
        $arguments = ['stream', 'id1', 'id2', 'id3'];
        $expected = ['stream', 'id1', 'id2', 'id3'];

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
    public function testRemovesSpecifiedMembers(): void
    {
        $redis = $this->getClient();

        $redis->xadd('stream', ['key0' => 'val0'], '0-1');
        $redis->xadd('stream', ['key1' => 'val1'], '1-1');
        $redis->xadd('stream', ['key2' => 'val2'], '2-1');

        $this->assertSame(2, $redis->xdel('stream', '0-1', '2-1', '99-1'));
        $this->assertSame(['1-1' => ['key1' => 'val1']], $redis->xrange('stream', '-', '+'));

        $this->assertSame(0, $redis->xdel('stream', '0-1'));
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
        $redis->xdel('foo', 'bar');
    }
}
