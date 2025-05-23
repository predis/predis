<?php

/*
 * This file is part of the Predis package.
 *
 * (c) 2009-2020 Daniele Alessandri
 * (c) 2021-2025 Till Krüss
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Command\Redis;

use Predis\Command\PrefixableCommand;

/**
 * @group commands
 * @group realm-list
 */
class LINDEX_Test extends PredisCommandTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getExpectedCommand(): string
    {
        return 'Predis\Command\Redis\LINDEX';
    }

    /**
     * {@inheritdoc}
     */
    protected function getExpectedId(): string
    {
        return 'LINDEX';
    }

    /**
     * @group disconnected
     */
    public function testFilterArguments(): void
    {
        $arguments = ['key', 1];
        $expected = ['key', 1];

        $command = $this->getCommand();
        $command->setArguments($arguments);

        $this->assertSame($expected, $command->getArguments());
    }

    /**
     * @group disconnected
     */
    public function testParseResponse(): void
    {
        $this->assertSame(0, $this->getCommand()->parseResponse(0));
    }

    /**
     * @group disconnected
     */
    public function testPrefixKeys(): void
    {
        /** @var PrefixableCommand $command */
        $command = $this->getCommand();
        $actualArguments = ['arg1', 'arg2', 'arg3', 'arg4'];
        $prefix = 'prefix:';
        $expectedArguments = ['prefix:arg1', 'arg2', 'arg3', 'arg4'];

        $command->setArguments($actualArguments);
        $command->prefixKeys($prefix);

        $this->assertSame($expectedArguments, $command->getArguments());
    }

    /**
     * @group connected
     */
    public function testReturnsElementAtIndex(): void
    {
        $redis = $this->getClient();

        $redis->rpush('letters', 'a', 'b', 'c', 'd', 'e');

        $this->assertSame('a', $redis->lindex('letters', 0));
        $this->assertSame('c', $redis->lindex('letters', 2));
        $this->assertNull($redis->lindex('letters', 100));
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 6.0.0
     */
    public function testReturnsElementAtIndexResp3(): void
    {
        $redis = $this->getResp3Client();

        $redis->rpush('letters', 'a', 'b', 'c', 'd', 'e');

        $this->assertSame('a', $redis->lindex('letters', 0));
        $this->assertSame('c', $redis->lindex('letters', 2));
        $this->assertNull($redis->lindex('letters', 100));
    }

    /**
     * @group connected
     */
    public function testReturnsElementAtNegativeIndex(): void
    {
        $redis = $this->getClient();

        $redis->rpush('letters', 'a', 'b', 'c', 'd', 'e');

        $this->assertSame('a', $redis->lindex('letters', -0));
        $this->assertSame('c', $redis->lindex('letters', -3));
        $this->assertSame('e', $redis->lindex('letters', -1));
        $this->assertNull($redis->lindex('letters', -100));
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
        $redis->lindex('foo', 0);
    }
}
