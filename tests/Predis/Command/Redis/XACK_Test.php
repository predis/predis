<?php

/*
 * This file is part of the Predis package.
 *
 * (c) 2009-2020 Daniele Alessandri
 * (c) 2021-2026 Till Krüss
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Command\Redis;

/**
 * @group commands
 * @group realm-stream
 */
class XACK_Test extends PredisCommandTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getExpectedCommand(): string
    {
        return 'Predis\Command\Redis\XACK';
    }

    /**
     * {@inheritdoc}
     */
    protected function getExpectedId(): string
    {
        return 'XACK';
    }

    /**
     * @group disconnected
     */
    public function testFilterArguments(): void
    {
        $arguments = ['stream', 'group', 'id1', 'id2', 'id3'];
        $expected = ['stream', 'group', 'id1', 'id2', 'id3'];

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
     * @group disconnected
     */
    public function testPrefixKeys(): void
    {
        $arguments = ['stream', 'group', 'id1', 'id2', 'id3'];
        $expected = ['prefix:stream', 'group', 'id1', 'id2', 'id3'];

        $command = $this->getCommandWithArgumentsArray($arguments);
        $command->prefixKeys('prefix:');

        $this->assertSame($expected, $command->getArguments());
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 5.0.0
     */
    public function testAck(): void
    {
        $redis = $this->getClient();

        $redis->xadd('stream', ['key0' => 'val0'], '0-1');
        $redis->xadd('stream', ['key1' => 'val1'], '1-1');
        $redis->xadd('stream', ['key2' => 'val2'], '2-1');

        $redis->xgroup->create('stream', 'group', '0');
        $redis->xreadgroup('group', 'consumer1', 1, null, false, 'stream', '>');
        $this->assertSame(1, $redis->xack('stream', 'group', '0-1'));
        $this->assertSame(0, $redis->xack('stream', 'group', '1-1'));
        $redis->xreadgroup('group', 'consumer1', 2, null, false, 'stream', '>');
        $this->assertSame(2, $redis->xack('stream', 'group', '1-1', '2-1'));
        $this->assertSame(0, $redis->xack('stream', 'group', '1-1', '2-1'));
    }
}
