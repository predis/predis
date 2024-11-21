<?php

/*
 * This file is part of the Predis package.
 *
 * (c) 2009-2020 Daniele Alessandri
 * (c) 2021-2024 Till Krüss
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
class CMSINCRBY_Test extends PredisCommandTestCase
{
    /**
     * {@inheritDoc}
     */
    protected function getExpectedCommand(): string
    {
        return CMSINCRBY::class;
    }

    /**
     * {@inheritDoc}
     */
    protected function getExpectedId(): string
    {
        return 'CMSINCRBY';
    }

    /**
     * @group disconnected
     */
    public function testFilterArguments(): void
    {
        $actualArguments = ['key', 'item1', 1, 'item2', 1];
        $expectedArguments = ['key', 'item1', 1, 'item2', 1];

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
     * @group connected
     * @group relay-resp3
     * @dataProvider sketchesProvider
     * @param  array $incrementArguments
     * @param  array $queryArguments
     * @param  array $expectedResponse
     * @return void
     * @requiresRedisBfVersion >= 2.0.0
     */
    public function testIncrementGivenItemsWithinCountMinSketch(
        array $incrementArguments,
        array $queryArguments,
        array $expectedResponse
    ): void {
        $redis = $this->getClient();

        $redis->cmsinitbydim('key', 2000, 7);

        $actualResponse = $redis->cmsincrby(...$incrementArguments);
        $queryResponse = $redis->cmsquery(...$queryArguments);

        $this->assertSame($expectedResponse, $actualResponse);
        $this->assertSame($expectedResponse, $queryResponse);
    }

    /**
     * @group connected
     * @requiresRedisBfVersion >= 2.0.0
     */
    public function testThrowsExceptionOnNonExistingKey(): void
    {
        $this->expectException(ServerException::class);
        $this->expectExceptionMessage('CMS: key does not exist');

        $redis = $this->getClient();

        $redis->cmsincrby('cmsincrby_foo', 'item1', 1);
    }

    public function sketchesProvider(): array
    {
        return [
            'with single item' => [
                ['key', 'item1', 1],
                ['key', 'item1'],
                [1],
            ],
            'with multiple items' => [
                ['key', 'item1', 1, 'item2', 1],
                ['key', 'item1', 'item2'],
                [1, 1],
            ],
            'with incrementing on X' => [
                ['key', 'item1', 2, 'item2', 5],
                ['key', 'item1', 'item2'],
                [2, 5],
            ],
        ];
    }
}
