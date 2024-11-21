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

namespace Predis\Command\Redis\CuckooFilter;

use Predis\Command\Redis\PredisCommandTestCase;
use Predis\Response\ServerException;

/**
 * @group commands
 * @group realm-stack
 */
class CFRESERVE_Test extends PredisCommandTestCase
{
    /**
     * {@inheritDoc}
     */
    protected function getExpectedCommand(): string
    {
        return CFRESERVE::class;
    }

    /**
     * {@inheritDoc}
     */
    protected function getExpectedId(): string
    {
        return 'CFRESERVE';
    }

    /**
     * @group disconnected
     * @dataProvider argumentsProvider
     */
    public function testFilterArguments(array $actualArguments, array $expectedArguments): void
    {
        $command = $this->getCommand();
        $command->setArguments($actualArguments);

        $this->assertSameValues($expectedArguments, $command->getArguments());
    }

    /**
     * @group connected
     * @group relay-resp3
     * @dataProvider filtersProvider
     * @param  array $filterArguments
     * @param  int   $expectedCapacity
     * @param  int   $expectedBucketSize
     * @param  int   $expectedMaxIterations
     * @param  int   $expectedExpansion
     * @return void
     * @requiresRedisBfVersion >= 1.0.0
     */
    public function testReserveCreatesCuckooFilterWithCorrectConfiguration(
        array $filterArguments,
        int $expectedCapacity,
        int $expectedBucketSize,
        int $expectedMaxIterations,
        int $expectedExpansion
    ): void {
        $redis = $this->getClient();

        $actualResponse = $redis->cfreserve(...$filterArguments);
        $this->assertEquals('OK', $actualResponse);

        $info = $redis->cfinfo('key');

        $this->assertSame($expectedCapacity, $info['Size']);
        $this->assertSame($expectedBucketSize, $info['Bucket size']);
        $this->assertSame($expectedMaxIterations, $info['Max iterations']);
        $this->assertSame($expectedExpansion, $info['Expansion rate']);
    }

    /**
     * @group connected
     * @requiresRedisBfVersion >= 1.0.0
     */
    public function testThrowsExceptionOnWrongType(): void
    {
        $this->expectException(ServerException::class);
        $this->expectExceptionMessage('Operation against a key holding the wrong kind of value');

        $redis = $this->getClient();

        $redis->set('cfreserve_foo', 'bar');
        $redis->cfreserve('cfreserve_foo', 500);
    }

    public function argumentsProvider(): array
    {
        return [
            'with default arguments' => [
                ['key', 500],
                ['key', 500],
            ],
            'with BUCKETSIZE argument' => [
                ['key', 500, 2],
                ['key', 500, 'BUCKETSIZE', 2],
            ],
            'with MAXITERATIONS argument' => [
                ['key', 500, -1, 15],
                ['key', 500, 'MAXITERATIONS', 15],
            ],
            'with EXPANSION argument' => [
                ['key', 500, -1, -1, 3],
                ['key', 500, 'EXPANSION', 3],
            ],
            'with all arguments' => [
                ['key', 500, 2, 15, 3],
                ['key', 500, 'BUCKETSIZE', 2, 'MAXITERATIONS', 15, 'EXPANSION', 3],
            ],
        ];
    }

    public function filtersProvider(): array
    {
        return [
            'with default arguments' => [
                ['key', 500, -1, -1, -1],
                568,
                2,
                20,
                1,
            ],
            'with modified bucket size' => [
                ['key', 1000, 3, -1, -1],
                1592,
                3,
                20,
                1,
            ],
            'with modified max iterations' => [
                ['key', 1000, -1, 15, -1],
                1080,
                2,
                15,
                1,
            ],
            'with modified expansion' => [
                ['key', 1000, -1, -1, 3],
                1080,
                2,
                20,
                4,
            ],
            'with all arguments' => [
                ['key', 1000, 3, 15, 3],
                1592,
                3,
                15,
                4,
            ],
        ];
    }
}
