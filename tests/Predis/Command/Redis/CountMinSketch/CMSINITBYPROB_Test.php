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

namespace Predis\Command\Redis\CountMinSketch;

use Predis\Command\PrefixableCommand;
use Predis\Command\Redis\PredisCommandTestCase;
use Predis\Response\ServerException;

/**
 * @group commands
 * @group realm-stack
 */
class CMSINITBYPROB_Test extends PredisCommandTestCase
{
    /**
     * {@inheritDoc}
     */
    protected function getExpectedCommand(): string
    {
        return CMSINITBYPROB::class;
    }

    /**
     * {@inheritDoc}
     */
    protected function getExpectedId(): string
    {
        return 'CMSINITBYPROB';
    }

    /**
     * @group disconnected
     */
    public function testFilterArguments(): void
    {
        $actualArguments = ['key', 0.001, 0.01];
        $expectedArguments = ['key', 0.001, 0.01];

        $command = $this->getCommand();
        $command->setArguments($actualArguments);

        $this->assertSameValues($expectedArguments, $command->getArguments());
    }

    /**
     * @group disconnected
     */
    public function testFilterArgumentsWithCellSize(): void
    {
        $actualArguments = ['key', 0.001, 0.01, 8];
        $expectedArguments = ['key', 0.001, 0.01, 'CELL_SIZE', 8];

        $command = $this->getCommand();
        $command->setArguments($actualArguments);

        $this->assertSameValues($expectedArguments, $command->getArguments());
    }

    /**
     * @group disconnected
     */
    public function testFilterArgumentsWithNullCellSizeOmitsIt(): void
    {
        $actualArguments = ['key', 0.001, 0.01, null];
        $expectedArguments = ['key', 0.001, 0.01];

        $command = $this->getCommand();
        $command->setArguments($actualArguments);

        $this->assertSameValues($expectedArguments, $command->getArguments());
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
        $actualArguments = ['arg1'];
        $prefix = 'prefix:';
        $expectedArguments = ['prefix:arg1'];

        $command->setArguments($actualArguments);
        $command->prefixKeys($prefix);

        $this->assertSame($expectedArguments, $command->getArguments());
    }

    /**
     * @group connected
     * @group relay-resp3
     * @return void
     * @requiresRedisBfVersion >= 2.0.0
     */
    public function testInitializeCountMinSketchWithDesiredErrorRateAndProbability(): void
    {
        $redis = $this->getClient();

        $actualResponse = $redis->cmsinitbyprob('key', 0.001, 0.01);
        $info = $redis->cmsinfo('key');

        $this->assertEquals('OK', $actualResponse);
        $this->assertSame(2000, $info['width']);
        $this->assertSame(7, $info['depth']);
    }

    /**
     * @group connected
     * @return void
     * @requiresRedisBfVersion >= 2.6.0
     */
    public function testInitializeCountMinSketchWithDesiredErrorRateAndProbabilityResp3(): void
    {
        $redis = $this->getResp3Client();

        $actualResponse = $redis->cmsinitbyprob('key', 0.001, 0.01);
        $info = $redis->cmsinfo('key');

        $this->assertEquals('OK', $actualResponse);
        $this->assertSame(2000, $info['width']);
        $this->assertSame(7, $info['depth']);
    }

    /**
     * @group connected
     * @group relay-resp3
     * @return void
     * @requiresRedisVersion >= 8.12.0
     */
    public function testInitializeCountMinSketchWithGivenCellSize(): void
    {
        $redis = $this->getClient();

        $actualResponse = $redis->cmsinitbyprob('key', 0.001, 0.01, 8);
        $info = $redis->cmsinfo('key');

        $this->assertEquals('OK', $actualResponse);
        $this->assertSame(2000, $info['width']);
        $this->assertSame(7, $info['depth']);
        $this->assertSame(8, $info['cell_size']);
    }

    /**
     * @group connected
     * @group relay-resp3
     * @requiresRedisVersion >= 8.12.0
     */
    public function testThrowsExceptionOnInvalidCellSize(): void
    {
        $this->expectException(ServerException::class);
        $this->expectExceptionMessage('CMS: CELL_SIZE must be 1, 2, 4 or 8');

        $redis = $this->getClient();

        $redis->cmsinitbyprob('cmsinitbyprob_invalid_cellsize', 0.001, 0.01, 3);
    }

    /**
     * @group connected
     * @requiresRedisBfVersion >= 2.0.0
     */
    public function testThrowsExceptionOnAlreadyExistingKey(): void
    {
        $this->expectException(ServerException::class);
        $this->expectExceptionMessage('CMS: key already exists');

        $redis = $this->getClient();

        $redis->set('cmsinitbydim_foo', 'bar');
        $redis->cmsinitbyprob('cmsinitbydim_foo', 0.001, 0.01);
    }
}
