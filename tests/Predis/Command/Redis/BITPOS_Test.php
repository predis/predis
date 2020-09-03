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
 * @group realm-string
 */
class BITPOS_Test extends PredisCommandTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getExpectedCommand(): string
    {
        return 'Predis\Command\Redis\BITPOS';
    }

    /**
     * {@inheritdoc}
     */
    protected function getExpectedId(): string
    {
        return 'BITPOS';
    }

    /**
     * @group disconnected
     */
    public function testFilterArguments(): void
    {
        $arguments = array('key', 0, 1, 10);
        $expected = array('key', 0, 1, 10);

        $command = $this->getCommand();
        $command->setArguments($arguments);

        $this->assertSame($expected, $command->getArguments());
    }

    /**
     * @group disconnected
     */
    public function testParseResponse(): void
    {
        $raw = 10;
        $expected = 10;
        $command = $this->getCommand();

        $this->assertSame($expected, $command->parseResponse($raw));
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 2.8.7
     */
    public function testReturnsBitPosition(): void
    {
        $redis = $this->getClient();

        $redis->setbit('key', 10, 0);
        $this->assertSame(0, $redis->bitpos('key', 0), 'Get position of first bit set to 0 - full range');
        $this->assertSame(-1, $redis->bitpos('key', 1), 'Get position of first bit set to 1 - full range');
        $this->assertSame(-1, $redis->bitpos('key', 1, 5, 10), 'Get position of first bit set to 1 - specific range');

        $redis->setbit('key', 5, 1);
        $this->assertSame(0, $redis->bitpos('key', 0), 'Get position of first bit set to 0 - full range');
        $this->assertSame(5, $redis->bitpos('key', 1), 'Get position of first bit set to 1 - full range');
        $this->assertSame(-1, $redis->bitpos('key', 1, 5, 10), 'Get position of first bit set to 1 - specific range');
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 2.8.7
     */
    public function testThrowsExceptionOnWrongType(): void
    {
        $this->expectException('Predis\Response\ServerException');
        $this->expectExceptionMessage('Operation against a key holding the wrong kind of value');

        $redis = $this->getClient();
        $redis->lpush('key', 'list');
        $redis->bitpos('key', 0);
    }
}
