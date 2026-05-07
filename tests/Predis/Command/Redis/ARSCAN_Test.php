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
class ARSCAN_Test extends PredisCommandTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getExpectedCommand(): string
    {
        return ARSCAN::class;
    }

    /**
     * {@inheritdoc}
     */
    protected function getExpectedId(): string
    {
        return 'ARSCAN';
    }

    /**
     * @group disconnected
     */
    public function testFilterArguments(): void
    {
        $arguments = ['key', 0, 10];
        $expected = ['key', 0, 10];

        $command = $this->getCommand();
        $command->setArguments($arguments);

        $this->assertSame($expected, $command->getArguments());
    }

    /**
     * @group disconnected
     */
    public function testFilterArgumentsWithLimit(): void
    {
        $arguments = ['key', 0, 10, 5];
        $expected = ['key', 0, 10, 'LIMIT', 5];

        $command = $this->getCommand();
        $command->setArguments($arguments);

        $this->assertSame($expected, $command->getArguments());
    }

    /**
     * @group disconnected
     */
    public function testParseResponse(): void
    {
        $response = [0, 'a', 1, 'b'];

        $this->assertSame($response, $this->getCommand()->parseResponse($response));
    }

    /**
     * @group disconnected
     */
    public function testPrefixKeys(): void
    {
        /** @var PrefixableCommand $command */
        $command = $this->getCommand();
        $actualArguments = ['arg1', 0, 10];
        $prefix = 'prefix:';
        $expectedArguments = ['prefix:arg1', 0, 10];

        $command->setArguments($actualArguments);
        $command->prefixKeys($prefix);

        $this->assertSame($expectedArguments, $command->getArguments());
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 8.8.0
     */
    public function testReturnsAlternatingIndexValuePairs(): void
    {
        $redis = $this->getClient();

        $redis->arset('arr', 0, 'a', 'b', 'c');

        $this->assertSame([0, 'a', 1, 'b', 2, 'c'], $redis->arscan('arr', 0, 2));
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 8.8.0
     */
    public function testSkipsEmptySlots(): void
    {
        $redis = $this->getClient();

        $redis->armset('arr', [0 => 'a', 5 => 'b', 10 => 'c']);

        $this->assertSame([0, 'a', 5, 'b', 10, 'c'], $redis->arscan('arr', 0, 10));
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 8.8.0
     */
    public function testReversesTraversalWhenStartGreaterThanEnd(): void
    {
        $redis = $this->getClient();

        $redis->arset('arr', 0, 'a', 'b', 'c');

        $this->assertSame([2, 'c', 1, 'b', 0, 'a'], $redis->arscan('arr', 2, 0));
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 8.8.0
     */
    public function testLimitCapsResults(): void
    {
        $redis = $this->getClient();

        $redis->arset('arr', 0, 'a', 'b', 'c', 'd');

        $this->assertSame([0, 'a', 1, 'b'], $redis->arscan('arr', 0, 3, 2));
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 8.8.0
     */
    public function testReturnsEmptyArrayOnMissingKey(): void
    {
        $redis = $this->getClient();

        $this->assertSame([], $redis->arscan('nonexistent', 0, 10));
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 8.8.0
     */
    public function testReturnsAlternatingPairsResp3(): void
    {
        $redis = $this->getResp3Client();

        $redis->arset('arr', 0, 'a', 'b', 'c');

        $this->assertSame([0, 'a', 1, 'b', 2, 'c'], $redis->arscan('arr', 0, 2));
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
        $redis->arscan('foo', 0, 10);
    }
}
