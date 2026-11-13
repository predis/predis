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

/**
 * @group commands
 * @group realm-zset
 */
class BZMOVE_Test extends PredisCommandTestCase
{
    /**
     * {@inheritDoc}
     */
    protected function getExpectedCommand(): string
    {
        return BZMOVE::class;
    }

    /**
     * {@inheritDoc}
     */
    protected function getExpectedId(): string
    {
        return 'BZMOVE';
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
    public function testPrefixKeys(): void
    {
        $command = $this->getCommand();
        $command->setArguments(['source', 'destination', 'max', 0.5, 2]);
        $command->prefixKeys('prefix:');

        $this->assertSame(
            ['prefix:source', 'prefix:destination', 'MAX', 0.5, 'COUNT', 2],
            $command->getArguments()
        );
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 8.11.0
     */
    public function testMovesMembersBetweenSortedSets(): void
    {
        $redis = $this->getClient();

        $redis->zadd('source', 1, 'a', 2, 'b', 3, 'c');

        $this->assertSame(['a' => 1.0, 'b' => 2.0], $redis->bzmove('source', 'destination', 'min', 0.1, 2));
        $this->assertSame(['c' => '3'], $redis->zrange('source', 0, -1, ['withscores' => true]));
        $this->assertSame(['a' => '1', 'b' => '2'], $redis->zrange('destination', 0, -1, ['withscores' => true]));
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 8.11.0
     */
    public function testMovesMembersBetweenSortedSetsResp3(): void
    {
        $redis = $this->getResp3Client();

        $redis->zadd('source', 1, 'a', 2, 'b');

        $this->assertSame(['b' => 2.0], $redis->bzmove('source', 'destination', 'max', 0.1));
    }

    /**
     * @group connected
     * @group slow
     * @requiresRedisVersion >= 8.11.0
     */
    public function testReturnsNullOnTimeout(): void
    {
        $redis = $this->getClient();

        $this->assertNull($redis->bzmove('source', 'destination', 'min', 0.1));
    }

    public function argumentsProvider(): array
    {
        return [
            'with required arguments only' => [
                ['source', 'destination', 'min', 1],
                ['source', 'destination', 'MIN', 1],
            ],
            'with COUNT' => [
                ['source', 'destination', 'MAX', 0.5, 3],
                ['source', 'destination', 'MAX', 0.5, 'COUNT', 3],
            ],
        ];
    }
}
