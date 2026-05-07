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
class ARSET_Test extends PredisCommandTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getExpectedCommand(): string
    {
        return ARSET::class;
    }

    /**
     * {@inheritdoc}
     */
    protected function getExpectedId(): string
    {
        return 'ARSET';
    }

    /**
     * @group disconnected
     */
    public function testFilterArguments(): void
    {
        $arguments = ['key', 0, 'value1', 'value2', 'value3'];
        $expected = ['key', 0, 'value1', 'value2', 'value3'];

        $command = $this->getCommand();
        $command->setArguments($arguments);

        $this->assertSame($expected, $command->getArguments());
    }

    /**
     * @group disconnected
     */
    public function testFilterArgumentsValuesAsSingleArray(): void
    {
        $arguments = ['key', 0, ['value1', 'value2', 'value3']];
        $expected = ['key', 0, 'value1', 'value2', 'value3'];

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
        /** @var PrefixableCommand $command */
        $command = $this->getCommand();
        $actualArguments = ['arg1', 0, 'arg3', 'arg4'];
        $prefix = 'prefix:';
        $expectedArguments = ['prefix:arg1', 0, 'arg3', 'arg4'];

        $command->setArguments($actualArguments);
        $command->prefixKeys($prefix);

        $this->assertSame($expectedArguments, $command->getArguments());
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 8.8.0
     */
    public function testSetsValueAtSpecifiedIndex(): void
    {
        $redis = $this->getClient();

        $this->assertSame(1, $redis->arset('arr', 0, 'foo'));
        $this->assertSame(1, $redis->arset('arr', 1, 'bar'));
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 8.8.0
     */
    public function testSetsMultipleContiguousValues(): void
    {
        $redis = $this->getClient();

        $this->assertSame(3, $redis->arset('arr', 0, 'a', 'b', 'c'));
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 8.8.0
     */
    public function testSetsMultipleContiguousValuesAsArray(): void
    {
        $redis = $this->getClient();

        $this->assertSame(3, $redis->arset('arr', 0, ['a', 'b', 'c']));
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 8.8.0
     */
    public function testReturnsZeroWhenOverwritingExistingSlots(): void
    {
        $redis = $this->getClient();

        $this->assertSame(3, $redis->arset('arr', 0, 'a', 'b', 'c'));
        $this->assertSame(0, $redis->arset('arr', 0, 'x', 'y', 'z'));
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 8.8.0
     */
    public function testThrowsExceptionOnNegativeIndex(): void
    {
        $this->expectException('Predis\Response\ServerException');
        $this->expectExceptionMessage('invalid array index');

        $redis = $this->getClient();

        $redis->arset('arr', 0, 'a', 'b', 'c');
        $redis->arset('arr', -1, 'C');
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 8.8.0
     */
    public function testSetsValueAtSpecifiedIndexResp3(): void
    {
        $redis = $this->getResp3Client();

        $this->assertSame(1, $redis->arset('arr', 0, 'foo'));
        $this->assertSame(3, $redis->arset('arr2', 0, 'a', 'b', 'c'));
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
        $redis->arset('foo', 0, 'baz');
    }
}
