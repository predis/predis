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

namespace Predis\Command\Redis;

class ZDIFFSTORE_Test extends PredisCommandTestCase
{
    /**
     * {@inheritDoc}
     */
    protected function getExpectedCommand(): string
    {
        return ZDIFFSTORE::class;
    }

    /**
     * {@inheritDoc}
     */
    protected function getExpectedId(): string
    {
        return 'ZDIFFSTORE';
    }

    /**
     * @group disconnected
     */
    public function testFilterArguments(): void
    {
        $actualArguments = ['zset_diff', ['key1', 'key2']];
        $expectedArguments = ['zset_diff', 2, 'key1', 'key2'];

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
     * @dataProvider sortedSetsProvider
     * @param  array $firstSetDictionary
     * @param  array $secondSetDictionary
     * @param  array $expectedResponse
     * @param  int   $expectedResultingElements
     * @return void
     * @requiresRedisVersion >= 6.2.0
     */
    public function testStoresDifferenceBetweenSortedSets(
        array $firstSetDictionary,
        array $secondSetDictionary,
        array $expectedResponse,
        int $expectedResultingElements
    ): void {
        $redis = $this->getClient();

        $redis->zadd('test-zset-1', ...$firstSetDictionary);
        $redis->zadd('test-zset-2', ...$secondSetDictionary);
        $actualResponse = $redis->zdiffstore('zdiffstore', ['test-zset-1', 'test-zset-2']);

        $this->assertSame($expectedResultingElements, $actualResponse);
        $this->assertSame($expectedResponse, $redis->zrange('zdiffstore', 0, -1));
    }

    public function sortedSetsProvider(): array
    {
        return [
            'no intersection' => [
                [1, 'member1', 2, 'member2', 3, 'member3'],
                [1, 'member4', 2, 'member5', 3, 'member6'],
                ['member1', 'member2', 'member3'],
                3,
            ],
            'partial intersection' => [
                [1, 'member1', 2, 'member2', 3, 'member3'],
                [1, 'member1', 2, 'member2', 3, 'member4'],
                ['member3'],
                1,
            ],
            'full intersection' => [
                [1, 'member1', 2, 'member2', 3, 'member3'],
                [1, 'member1', 2, 'member2', 3, 'member3'],
                [],
                0,
            ],
        ];
    }
}
