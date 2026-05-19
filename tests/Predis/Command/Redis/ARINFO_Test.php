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
class ARINFO_Test extends PredisCommandTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getExpectedCommand(): string
    {
        return ARINFO::class;
    }

    /**
     * {@inheritdoc}
     */
    protected function getExpectedId(): string
    {
        return 'ARINFO';
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
    public function testFilterArgumentsWithFull(): void
    {
        $arguments = ['key', true];
        $expected = ['key', 'FULL'];

        $command = $this->getCommand();
        $command->setArguments($arguments);

        $this->assertSame($expected, $command->getArguments());
    }

    /**
     * @group disconnected
     */
    public function testParseResponseConvertsFlatArrayToDictionary(): void
    {
        $resp2 = ['count', 3, 'len', 5, 'next-insert-index', 5];
        $expected = ['count' => 3, 'len' => 5, 'next-insert-index' => 5];

        $this->assertSame($expected, $this->getCommand()->parseResponse($resp2));
    }

    /**
     * @group disconnected
     */
    public function testParseResponsePassesThroughDictionary(): void
    {
        $resp3 = ['count' => 3, 'len' => 5];

        $this->assertSame($resp3, $this->getCommand()->parseResponse($resp3));
    }

    /**
     * @group disconnected
     */
    public function testPrefixKeys(): void
    {
        /** @var PrefixableCommand $command */
        $command = $this->getCommand();
        $actualArguments = ['arg1'];
        $prefix = 'prefix:';
        $expectedArguments = ['prefix:arg1'];

        $command->setArguments($actualArguments);
        $command->prefixKeys($prefix);

        $this->assertSame($expectedArguments, $command->getArguments());
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 8.8.0
     */
    public function testReturnsTopLevelMetadata(): void
    {
        $redis = $this->getClient();

        $redis->arset('arr', 0, 'a', 'b', 'c');

        $info = $redis->arinfo('arr');

        $this->assertIsArray($info);
        $this->assertArrayHasKey('count', $info);
        $this->assertArrayHasKey('len', $info);
        $this->assertArrayHasKey('next-insert-index', $info);
        $this->assertArrayHasKey('slices', $info);
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 8.8.0
     */
    public function testFullIncludesPerSliceStatistics(): void
    {
        $redis = $this->getClient();

        $redis->arset('arr', 0, 'a', 'b', 'c');

        $info = $redis->arinfo('arr', true);

        $this->assertIsArray($info);
        $this->assertArrayHasKey('count', $info);
        $this->assertArrayHasKey('dense-slices', $info);
        $this->assertArrayHasKey('sparse-slices', $info);
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 8.8.0
     */
    public function testThrowsExceptionWhenKeyDoesNotExist(): void
    {
        $this->expectException('Predis\Response\ServerException');
        $this->expectExceptionMessage('no such key');

        $redis = $this->getClient();
        $redis->arinfo('nonexistent');
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 8.8.0
     */
    public function testReturnsTopLevelMetadataResp3(): void
    {
        $redis = $this->getResp3Client();

        $redis->arset('arr', 0, 'a', 'b', 'c');

        $info = $redis->arinfo('arr');

        $this->assertIsArray($info);
        $this->assertArrayHasKey('count', $info);
        $this->assertArrayHasKey('len', $info);
    }
}
