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

use Predis\Command\PrefixableCommand;

/**
 * @group commands
 * @group realm-array
 */
class ARGETRANGE_Test extends PredisCommandTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getExpectedCommand(): string
    {
        return ARGETRANGE::class;
    }

    /**
     * {@inheritdoc}
     */
    protected function getExpectedId(): string
    {
        return 'ARGETRANGE';
    }

    /**
     * @group disconnected
     */
    public function testFilterArguments(): void
    {
        $arguments = ['key', 0, 5];
        $expected = ['key', 0, 5];

        $command = $this->getCommand();
        $command->setArguments($arguments);

        $this->assertSame($expected, $command->getArguments());
    }

    /**
     * @group disconnected
     */
    public function testParseResponse(): void
    {
        $this->assertSame(['a', 'b', 'c'], $this->getCommand()->parseResponse(['a', 'b', 'c']));
    }

    /**
     * @group disconnected
     */
    public function testPrefixKeys(): void
    {
        /** @var PrefixableCommand $command */
        $command = $this->getCommand();
        $actualArguments = ['arg1', 0, 5];
        $prefix = 'prefix:';
        $expectedArguments = ['prefix:arg1', 0, 5];

        $command->setArguments($actualArguments);
        $command->prefixKeys($prefix);

        $this->assertSame($expectedArguments, $command->getArguments());
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 8.8.0
     */
    public function testReturnsArraySlice(): void
    {
        $redis = $this->getClient();

        $redis->arset('arr', 0, 'a', 'b', 'c', 'd', 'e');

        $this->assertSame(['a', 'b', 'c'], $redis->argetrange('arr', 0, 2));
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 8.8.0
     */
    public function testReturnsReversedArraySlice(): void
    {
        $redis = $this->getClient();

        $redis->arset('arr', 0, 'a', 'b', 'c', 'd', 'e');

        $this->assertSame(['c', 'b', 'a'], $redis->argetrange('arr', 2, 0));
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 8.8.0
     */
    public function testReturnsNullsOnNonExistingKey(): void
    {
        $redis = $this->getClient();

        $this->assertSame(array_fill(0, 11, null), $redis->argetrange('nonexistent', 0, 10));
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 8.8.0
     */
    public function testReturnsArraySliceResp3(): void
    {
        $redis = $this->getResp3Client();

        $redis->arset('arr', 0, 'a', 'b', 'c', 'd', 'e');

        $this->assertSame(['a', 'b', 'c'], $redis->argetrange('arr', 0, 2));
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 8.8.0
     */
    public function testThrowsExceptionOnWrongType(): void
    {
        $this->expectException('Predis\Response\ServerException');
        $this->expectExceptionMessage('Operation against a key holding the wrong kind of value');

        $redis = $this->getClient();

        $redis->set('foo', 'bar');
        $redis->argetrange('foo', 0, 10);
    }
}
