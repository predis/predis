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

use Predis\Response\ServerException;

class ZDIFF_test extends PredisCommandTestCase
{
    /**
     * {@inheritDoc}
     */
    protected function getExpectedCommand(): string
    {
        return ZDIFF::class;
    }

    /**
     * {@inheritDoc}
     */
    protected function getExpectedId(): string
    {
        return 'ZDIFF';
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
     * @dataProvider sortedSetsProvider
     * @param  array $firstSetDictionary
     * @param  array $secondSetDictionary
     * @param  array $expectedResponse
     * @param  bool  $withScores
     * @return void
     * @requiresRedisVersion >= 6.2.0
     */
    public function testReturnsDifferenceBetweenSortedSets(
        array $firstSetDictionary,
        array $secondSetDictionary,
        array $expectedResponse,
        bool $withScores
    ): void {
        $redis = $this->getClient();

        $redis->zadd('test-zset-1', ...$firstSetDictionary);
        $redis->zadd('test-zset-2', ...$secondSetDictionary);

        $this->assertSame($expectedResponse, $redis->zdiff(['test-zset-1', 'test-zset-2'], $withScores));
    }

    /**
     * @group connected
     * @return void
     * @requiresRedisVersion >= 6.2.0
     */
    public function testReturnsNoIntersectionOnNonExistingKey(): void
    {
        $redis = $this->getClient();
        $membersDictionary = [1, 'member1', 2, 'member2', 3, 'member3'];
        $expectedResponse = ['member1' => '1', 'member2' => '2', 'member3' => '3'];

        $redis->zadd('test-zset-1', ...$membersDictionary);
        $this->assertSame($expectedResponse, $redis->zdiff(['test-zset-1', 'test-zset-2'], true));
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 6.2.0
     */
    public function testThrowsExceptionOnWrongType(): void
    {
        $this->expectException(ServerException::class);
        $this->expectExceptionMessage('Operation against a key holding the wrong kind of value');

        $redis = $this->getClient();

        $redis->set('zdiff_foo', 'bar');
        $redis->zdiff(['zdiff_foo'], true);
    }

    public function argumentsProvider(): array
    {
        return [
            'with scores' => [[['zset'], 5, 'withScores' => true], [1, 'zset', 5, 'WITHSCORES']],
            'without scores' => [[['zset'], 5], [1, 'zset', 5]],
            'without scores - false value' => [[['zset'], 5, 'withScores' => false], [1, 'zset', 5]],
        ];
    }

    public function sortedSetsProvider(): array
    {
        return [
            'no intersection - without score' => [
                [1, 'member1', 2, 'member2', 3, 'member3'],
                [1, 'member4', 2, 'member5', 3, 'member6'],
                ['member1', 'member2', 'member3'],
                false,
            ],
            'partial intersection - without score' => [
                [1, 'member1', 2, 'member2', 3, 'member3'],
                [1, 'member1', 2, 'member2', 3, 'member4'],
                ['member3'],
                false,
            ],
            'full intersection' => [
                [1, 'member1', 2, 'member2', 3, 'member3'],
                [1, 'member1', 2, 'member2', 3, 'member3'],
                [],
                false,
            ],
            'no intersection - with score' => [
                [1, 'member1', 2, 'member2', 3, 'member3'],
                [1, 'member4', 2, 'member5', 3, 'member6'],
                ['member1' => '1', 'member2' => '2', 'member3' => '3'],
                true,
            ],
            'partial intersection - with score' => [
                [1, 'member1', 2, 'member2', 3, 'member3'],
                [1, 'member1', 2, 'member2', 3, 'member4'],
                ['member3' => '3'],
                true,
            ],
        ];
    }
}
