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
class ARMSET_Test extends PredisCommandTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getExpectedCommand(): string
    {
        return ARMSET::class;
    }

    /**
     * {@inheritdoc}
     */
    protected function getExpectedId(): string
    {
        return 'ARMSET';
    }

    /**
     * @group disconnected
     */
    public function testFilterArgumentsFlat(): void
    {
        $arguments = ['key', 0, 'a', 5, 'b'];
        $expected = ['key', 0, 'a', 5, 'b'];

        $command = $this->getCommand();
        $command->setArguments($arguments);

        $this->assertSame($expected, $command->getArguments());
    }

    /**
     * @group disconnected
     */
    public function testFilterArgumentsAsDictionary(): void
    {
        $arguments = ['key', [0 => 'a', 5 => 'b', 10 => 'c']];
        $expected = ['key', 0, 'a', 5, 'b', 10, 'c'];

        $command = $this->getCommand();
        $command->setArguments($arguments);

        $this->assertSame($expected, $command->getArguments());
    }

    /**
     * @group disconnected
     */
    public function testParseResponse(): void
    {
        $this->assertSame(3, $this->getCommand()->parseResponse(3));
    }

    /**
     * @group disconnected
     */
    public function testPrefixKeys(): void
    {
        /** @var PrefixableCommand $command */
        $command = $this->getCommand();
        $actualArguments = ['arg1', 0, 'a'];
        $prefix = 'prefix:';
        $expectedArguments = ['prefix:arg1', 0, 'a'];

        $command->setArguments($actualArguments);
        $command->prefixKeys($prefix);

        $this->assertSame($expectedArguments, $command->getArguments());
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 8.8.0
     */
    public function testSetsMultipleValuesAtCorrectIndices(): void
    {
        $redis = $this->getClient();

        $this->assertSame(3, $redis->armset('arr', [0 => 'a', 5 => 'b', 10 => 'c']));
        $this->assertSame('a', $redis->arget('arr', 0));
        $this->assertSame('b', $redis->arget('arr', 5));
        $this->assertSame('c', $redis->arget('arr', 10));
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 8.8.0
     */
    public function testReturnsZeroWhenOverwritingExistingSlots(): void
    {
        $redis = $this->getClient();

        $redis->armset('arr', [0 => 'a', 1 => 'b']);

        $this->assertSame(0, $redis->armset('arr', [0 => 'x', 1 => 'y']));
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 8.8.0
     */
    public function testSetsMultipleValuesResp3(): void
    {
        $redis = $this->getResp3Client();

        $this->assertSame(2, $redis->armset('arr', [0 => 'a', 5 => 'b']));
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
        $redis->armset('foo', [0 => 'baz']);
    }
}
