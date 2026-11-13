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
class ZMOVEMEMBER_Test extends PredisCommandTestCase
{
    /**
     * {@inheritDoc}
     */
    protected function getExpectedCommand(): string
    {
        return ZMOVEMEMBER::class;
    }

    /**
     * {@inheritDoc}
     */
    protected function getExpectedId(): string
    {
        return 'ZMOVEMEMBER';
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
        $command->setArguments(['source', 'destination', ['a']]);
        $command->prefixKeys('prefix:');

        $this->assertSame(['prefix:source', 'prefix:destination', 'MEMBERS', 1, 'a'], $command->getArguments());
    }

    /**
     * @group connected
     * @dataProvider movesProvider
     * @requiresRedisVersion >= 8.11.0
     */
    public function testMovesMembersBetweenSortedSets(
        array $commandArguments,
        ?array $expectedResponse,
        array $expectedSource,
        array $expectedDestination
    ): void {
        $redis = $this->getClient();

        $redis->zadd('source', 1, 'a', 2, 'b', 3, 'c');
        $redis->zadd('destination', 10, 'b');

        $this->assertSame($expectedResponse, $redis->zmovemember('source', 'destination', ...$commandArguments));
        $this->assertSame($expectedSource, $redis->zrange('source', 0, -1, ['withscores' => true]));
        $this->assertSame($expectedDestination, $redis->zrange('destination', 0, -1, ['withscores' => true]));
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 8.11.0
     */
    public function testMovesMembersBetweenSortedSetsResp3(): void
    {
        $redis = $this->getResp3Client();

        $redis->zadd('source', 1, 'a', 2, 'b');

        $this->assertSame(['b' => 2.0, 'a' => 1.0], $redis->zmovemember('source', 'destination', ['b', 'a']));
    }

    public function argumentsProvider(): array
    {
        return [
            'with members only' => [
                ['source', 'destination', ['a', 'b']],
                ['source', 'destination', 'MEMBERS', 2, 'a', 'b'],
            ],
            'with NX condition' => [
                ['source', 'destination', ['a'], 'nx'],
                ['source', 'destination', 'NX', 'MEMBERS', 1, 'a'],
            ],
            'with XX condition' => [
                ['source', 'destination', ['a', 'b'], 'XX'],
                ['source', 'destination', 'XX', 'MEMBERS', 2, 'a', 'b'],
            ],
        ];
    }

    public function movesProvider(): array
    {
        return [
            'moves given members, overwriting destination score' => [
                [['c', 'b', 'missing']],
                ['c' => 3.0, 'b' => 2.0],
                ['a' => '1'],
                ['b' => '2', 'c' => '3'],
            ],
            'NX skips members present in destination' => [
                [['a', 'b'], 'NX'],
                ['a' => 1.0],
                ['b' => '2', 'c' => '3'],
                ['a' => '1', 'b' => '10'],
            ],
            'XX moves only members present in destination' => [
                [['a', 'b'], 'XX'],
                ['b' => 2.0],
                ['a' => '1', 'c' => '3'],
                ['b' => '2'],
            ],
            'missing members move nothing' => [
                [['x', 'y']],
                null,
                ['a' => '1', 'b' => '2', 'c' => '3'],
                ['b' => '10'],
            ],
        ];
    }
}
