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

class ZRANDMEMBER_test extends PredisCommandTestCase
{
    /**
     * {@inheritDoc}
     */
    protected function getExpectedCommand(): string
    {
        return ZRANDMEMBER::class;
    }

    /**
     * {@inheritDoc}
     */
    protected function getExpectedId(): string
    {
        return 'ZRANDMEMBER';
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
     * @param  string $key
     * @param  int    $count
     * @param  array  $membersDictionary
     * @param  array  $expectedResponse
     * @param  bool   $withScores
     * @return void
     * @dataProvider membersProvider
     * @requiresRedisVersion >= 6.2.0
     */
    public function testReturnsRandomMembersFromSortedSet(
        string $key,
        int $count,
        array $membersDictionary,
        array $expectedResponse,
        bool $withScores
    ): void {
        $redis = $this->getClient();
        $notExpectedKey = 'not_expected';

        $redis->zadd($key, ...$membersDictionary);
        $this->assertSameValues($redis->zrandmember($key, $count, $withScores), $expectedResponse);
        $this->assertNull($redis->zrandmember($notExpectedKey));
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

        $redis->set('zrandmember_foo', 'bar');
        $redis->zrandmember('zrandmember_foo', 1, true);
    }

    public function argumentsProvider(): array
    {
        return [
            'with scores' => [['zset', 5, 'withScores' => true], ['zset', 5, 'WITHSCORES']],
            'without scores' => [['zset', 5], ['zset', 5]],
            'without scores - false value' => [['zset', 5, 'withScores' => false], ['zset', 5]],
        ];
    }

    public function membersProvider(): array
    {
        return [
            'one member - without score' => ['test-zset', 1, [1, 'member1'], ['member1'], false],
            'multiple members - positive count - without score' => [
                'test-zset',
                2,
                [1, 'member1', 2, 'member2'],
                ['member1', 'member2'],
                false,
            ],
            'multiple members - negative count - without score' => [
                'test-zset',
                -2,
                [1, 'member1'],
                ['member1', 'member1'],
                false,
            ],
            'one member - with score' => ['test-zset', 1, [1, 'member1'], ['member1' => '1'], true],
            'multiple members - positive count - with score' => [
                'test-zset',
                2,
                [1, 'member1', 2, 'member2'],
                ['member1' => '1', 'member2' => '2'],
                true,
            ],
            'multiple members - negative count - with score' => [
                'test-zset',
                -1,
                [1, 'member1'],
                ['member1' => '1'],
                true,
            ],
        ];
    }
}
