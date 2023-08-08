<?php

/*
 * This file is part of the Predis package.
 *
 * (c) 2009-2020 Daniele Alessandri
 * (c) 2021-2023 Till Krüss
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Command\Redis\CountMinSketch;

use Predis\Command\Redis\PredisCommandTestCase;
use Predis\Response\ServerException;

/**
 * @group commands
 * @group realm-stack
 */
class CMSMERGE_Test extends PredisCommandTestCase
{
    /**
     * {@inheritDoc}
     */
    protected function getExpectedCommand(): string
    {
        return CMSMERGE::class;
    }

    /**
     * {@inheritDoc}
     */
    protected function getExpectedId(): string
    {
        return 'CMSMERGE';
    }

    /**
     * @group disconnected
     * @dataProvider argumentsProvider
     */
    public function testFilterArguments(array $actualArguments, array $expectedArguments): void
    {
        $command = $this->getCommand();
        $command->setArguments($actualArguments);

        $this->assertSame($expectedArguments, $command->getArguments());
    }

    /**
     * @group disconnected
     */
    public function testParseResponse(): void
    {
        $this->assertSame(1, $this->getCommand()->parseResponse(1));
    }

    /**
     * @group connected
     * @group relay-incompatible
     * @dataProvider sketchesProvider
     * @param  array  $mergeArguments
     * @param  string $destinationKey
     * @param  array  $items
     * @param  array  $expectedCounts
     * @return void
     * @requiresRedisBfVersion >= 2.0.0
     */
    public function testMergeSketchesAndSaveWithinDestinationCountMinSketch(
        array $mergeArguments,
        string $destinationKey,
        array $items,
        array $expectedCounts
    ): void {
        $redis = $this->getClient();

        $redis->cmsinitbyprob('source1', 0.001, 0.01);
        $redis->cmsinitbyprob('source2', 0.001, 0.01);
        $redis->cmsinitbyprob('destination', 0.001, 0.01);
        $redis->cmsincrby('source1', 'item1', 1, 'item2', 1);
        $redis->cmsincrby('source2', 'item1', 1, 'item2', 1);

        $actualResponse = $redis->cmsmerge(...$mergeArguments);

        $this->assertEquals('OK', $actualResponse);
        $this->assertSame($expectedCounts, $redis->cmsquery('destination', ...$items));
    }

    /**
     * @group connected
     * @return void
     * @requiresRedisBfVersion >= 2.6.0
     */
    public function testMergeSketchesAndSaveWithinDestinationCountMinSketchResp3(): void
    {
        $redis = $this->getResp3Client();

        $redis->cmsinitbyprob('source1', 0.001, 0.01);
        $redis->cmsinitbyprob('source2', 0.001, 0.01);
        $redis->cmsinitbyprob('destination', 0.001, 0.01);
        $redis->cmsincrby('source1', 'item1', 1, 'item2', 1);
        $redis->cmsincrby('source2', 'item1', 1, 'item2', 1);

        $actualResponse = $redis->cmsmerge('destination', ['source1', 'source2']);

        $this->assertEquals('OK', $actualResponse);
        $this->assertSame([2, 2], $redis->cmsquery('destination', 'item1', 'item2'));
    }

    /**
     * @group connected
     * @group relay-incompatible
     * @return void
     * @requiresRedisBfVersion >= 2.0.0
     */
    public function testThrowsExceptionOnNonExistingDestinationCountMinSketch(): void
    {
        $redis = $this->getClient();

        $redis->cmsinitbyprob('source1', 0.001, 0.01);
        $redis->cmsinitbyprob('source2', 0.001, 0.01);

        $this->expectException(ServerException::class);
        $this->expectExceptionMessage('CMS: key does not exist');

        $redis->cmsmerge('destination', ['source1', 'source2']);
    }

    /**
     * @group connected
     * @group relay-incompatible
     * @return void
     * @requiresRedisBfVersion >= 2.0.0
     */
    public function testThrowsExceptionOnNonExistingSourceCountMinSketch(): void
    {
        $redis = $this->getClient();

        $redis->cmsinitbyprob('source1', 0.001, 0.01);
        $redis->cmsinitbyprob('destination', 0.001, 0.01);

        $this->expectException(ServerException::class);
        $this->expectExceptionMessage('CMS: key does not exist');

        $redis->cmsmerge('destination', ['source1', 'source2']);
    }

    /**
     * @group connected
     * @group relay-incompatible
     * @return void
     * @requiresRedisBfVersion >= 2.0.0
     */
    public function testThrowsExceptionOnDifferentCountMinSketchesConfigurations(): void
    {
        $redis = $this->getClient();

        $redis->cmsinitbyprob('source1', 0.001, 0.01);
        $redis->cmsinitbyprob('source2', 0.01, 0.01);
        $redis->cmsinitbyprob('destination', 0.001, 0.01);

        $this->expectException(ServerException::class);
        $this->expectExceptionMessage('CMS: width/depth is not equal');

        $redis->cmsmerge('destination', ['source1', 'source2']);
    }

    public function argumentsProvider(): array
    {
        return [
            'with default arguments' => [
                ['destination', ['source']],
                ['destination', 1, 'source'],
            ],
            'with multiple sources' => [
                ['destination', ['source1', 'source2']],
                ['destination', 2, 'source1', 'source2'],
            ],
            'with WEIGHTS' => [
                ['destination', ['source1', 'source2'], [1, 3]],
                ['destination', 2, 'source1', 'source2', 'WEIGHTS', 1, 3],
            ],
        ];
    }

    public function sketchesProvider(): array
    {
        return [
            'with default arguments' => [
                ['destination', ['source1', 'source2']],
                'destination',
                ['item1', 'item2'],
                [2, 2],
            ],
            'with modified WEIGHTS' => [
                ['destination', ['source1', 'source2'], [2, 4]],
                'destination',
                ['item1', 'item2'],
                [6, 6],
            ],
        ];
    }
}
