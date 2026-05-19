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
class ARDELRANGE_Test extends PredisCommandTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getExpectedCommand(): string
    {
        return ARDELRANGE::class;
    }

    /**
     * {@inheritdoc}
     */
    protected function getExpectedId(): string
    {
        return 'ARDELRANGE';
    }

    /**
     * @group disconnected
     */
    public function testFilterArgumentsFlat(): void
    {
        $arguments = ['key', 0, 3, 5, 10];
        $expected = ['key', 0, 3, 5, 10];

        $command = $this->getCommand();
        $command->setArguments($arguments);

        $this->assertSame($expected, $command->getArguments());
    }

    /**
     * @group disconnected
     */
    public function testFilterArgumentsAsPairsArray(): void
    {
        $arguments = ['key', [[0, 3], [5, 10]]];
        $expected = ['key', 0, 3, 5, 10];

        $command = $this->getCommand();
        $command->setArguments($arguments);

        $this->assertSame($expected, $command->getArguments());
    }

    /**
     * @group disconnected
     */
    public function testFilterArgumentsAsFlatArray(): void
    {
        $arguments = ['key', [0, 3, 5, 10]];
        $expected = ['key', 0, 3, 5, 10];

        $command = $this->getCommand();
        $command->setArguments($arguments);

        $this->assertSame($expected, $command->getArguments());
    }

    /**
     * @group disconnected
     */
    public function testParseResponse(): void
    {
        $this->assertSame(5, $this->getCommand()->parseResponse(5));
    }

    /**
     * @group disconnected
     */
    public function testPrefixKeys(): void
    {
        /** @var PrefixableCommand $command */
        $command = $this->getCommand();
        $actualArguments = ['arg1', 0, 3];
        $prefix = 'prefix:';
        $expectedArguments = ['prefix:arg1', 0, 3];

        $command->setArguments($actualArguments);
        $command->prefixKeys($prefix);

        $this->assertSame($expectedArguments, $command->getArguments());
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 8.8.0
     */
    public function testReturnsNumberOfDeletedElementsInRange(): void
    {
        $redis = $this->getClient();

        $redis->arset('arr', 0, 'a', 'b', 'c', 'd', 'e');

        $this->assertSame(3, $redis->ardelrange('arr', 0, 2));
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 8.8.0
     */
    public function testReturnsNumberOfDeletedElementsInReversedRange(): void
    {
        $redis = $this->getClient();

        $redis->arset('arr', 0, 'a', 'b', 'c', 'd', 'e');

        $this->assertSame(3, $redis->ardelrange('arr', 2, 0));
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 8.8.0
     */
    public function testReturnsZeroOnNonExistingKey(): void
    {
        $redis = $this->getClient();

        $this->assertSame(0, $redis->ardelrange('nonexistent', 0, 10));
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 8.8.0
     */
    public function testDeletesMultipleRanges(): void
    {
        $redis = $this->getClient();

        $redis->arset('arr', 0, 'a', 'b', 'c', 'd', 'e', 'f', 'g');

        $this->assertSame(4, $redis->ardelrange('arr', [[0, 1], [4, 5]]));
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 8.8.0
     */
    public function testReturnsNumberOfDeletedElementsResp3(): void
    {
        $redis = $this->getResp3Client();

        $redis->arset('arr', 0, 'a', 'b', 'c', 'd', 'e');

        $this->assertSame(3, $redis->ardelrange('arr', 0, 2));
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
        $redis->ardelrange('foo', 0, 10);
    }
}
