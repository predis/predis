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

use UnexpectedValueException;

class ZRANGESTORE_Test extends PredisCommandTestCase
{
    /**
     * {@inheritDoc}
     */
    protected function getExpectedCommand(): string
    {
        return ZRANGESTORE::class;
    }

    /**
     * {@inheritDoc}
     */
    protected function getExpectedId(): string
    {
        return 'ZRANGESTORE';
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
     * @group disconnected
     */
    public function testParseResponse(): void
    {
        $this->assertSame(1, $this->getCommand()->parseResponse(1));
    }

    /**
     * @group connected
     * @dataProvider rangesProvider
     * @param array       $actualSortedSet
     * @param int|string  $min
     * @param int|string  $max
     * @param string|bool $by
     * @param bool        $rev
     * @param bool        $limit
     * @param int         $offset
     * @param int         $count
     * @param int         $expectedResultingElements
     * @param array       $expectedResponse
     * @requiresRedisVersion >= 6.2.0
     * @return void
     */
    public function testStoresSortedSetRanges(
        array $actualSortedSet,
        $min,
        $max,
        $by,
        bool $rev,
        bool $limit,
        int $offset,
        int $count,
        int $expectedResultingElements,
        array $expectedResponse
    ): void {
        $redis = $this->getClient();

        $redis->zadd('source', ...$actualSortedSet);
        $actualResponse = $redis->zrangestore(
            'destination',
            'source',
            $min,
            $max,
            $by,
            $rev,
            $limit,
            $offset,
            $count
        );

        $this->assertSame($expectedResultingElements, $actualResponse);
        $this->assertSame($expectedResponse, $redis->zrange('destination', 0, -1));
    }

    /**
     * @group connected
     * @dataProvider unexpectedValuesProvider
     * @param  int|string  $min
     * @param  int|string  $max
     * @param  string|bool $by
     * @param              $rev
     * @param              $limit
     * @param  int         $offset
     * @param  int         $count
     * @param  string      $expectedExceptionMessage
     * @return void
     * @requiresRedisVersion >= 6.2.0
     */
    public function testThrowsExceptionOnUnexpectedValuesGiven(
        $min,
        $max,
        $by,
        $rev,
        $limit,
        int $offset,
        int $count,
        string $expectedExceptionMessage
    ): void {
        $redis = $this->getClient();

        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage($expectedExceptionMessage);

        $redis->zrangestore(
            'destination',
            'source',
            $min,
            $max,
            $by,
            $rev,
            $limit,
            $offset,
            $count
        );
    }

    public function argumentsProvider(): array
    {
        return [
            'without optional arguments' => [
                ['destination', 'source', 0, -1, false, false, false, 0, 0],
                ['destination', 'source', 0, -1],
            ],
            'with BYLEX argument' => [
                ['destination', 'source', 0, -1, 'bylex', false, false, 0, 0],
                ['destination', 'source', 0, -1, 'BYLEX'],
            ],
            'with BYSCORE argument' => [
                ['destination', 'source', 0, -1, 'byscore', false, false, 0, 0],
                ['destination', 'source', 0, -1, 'BYSCORE'],
            ],
            'with REV argument' => [
                ['destination', 'source', 0, -1, false, true, false, 0, 0],
                ['destination', 'source', 0, -1, 'REV'],
            ],
            'with BYSCORE/BYLEX and LIMIT argument' => [
                ['destination', 'source', 0, -1, 'byscore', false, true, 0, 1],
                ['destination', 'source', 0, -1, 'BYSCORE', 'LIMIT', 0, 1],
            ],
            'with BYSCORE/BYLEX argument and REV argument' => [
                ['destination', 'source', 0, -1, 'byscore', true, false, 0, 0],
                ['destination', 'source', 0, -1, 'BYSCORE', 'REV'],
            ],
            'with BYSCORE/BYLEX argument, REV argument and LIMIT' => [
                ['destination', 'source', 0, -1, 'bylex', true, true, 0, 1],
                ['destination', 'source', 0, -1, 'BYLEX', 'REV', 'LIMIT', 0, 1],
            ],
        ];
    }

    public function rangesProvider(): array
    {
        return [
            'without optional arguments' => [
                [1, 'member1', 2, 'member2', 3, 'member3'],
                0,
                -1,
                false,
                false,
                false,
                0,
                0,
                3,
                ['member1', 'member2', 'member3'],
            ],
            'with BYLEX argument' => [
                [1, 'abc', 1, 'abb', 1, 'aaa'],
                '[aaa',
                '[abc',
                'bylex',
                false,
                false,
                0,
                0,
                3,
                ['aaa', 'abb', 'abc'],
            ],
            'with BYSCORE argument' => [
                [3, 'member1', 2, 'member2', 1, 'member3'],
                '1',
                '(4',
                'byscore',
                false,
                false,
                0,
                0,
                3,
                ['member3', 'member2', 'member1'],
            ],
            'with REV argument' => [
                [3, 'member1', 2, 'member2', 1, 'member3'],
                0,
                2,
                false,
                true,
                false,
                0,
                0,
                3,
                ['member3', 'member2', 'member1'],
            ],
            'with BYSCORE/BYLEX and LIMIT argument' => [
                [1, 'member1', 2, 'member2', 3, 'member3'],
                '1',
                '(4',
                'byscore',
                false,
                true,
                0,
                1,
                1,
                ['member1'],
            ],
            'with BYSCORE/BYLEX argument and REV argument' => [
                [3, 'member1', 2, 'member2', 1, 'member3'],
                3,
                0,
                'byscore',
                true,
                false,
                0,
                0,
                3,
                ['member3', 'member2', 'member1'],
            ],
            'with BYSCORE/BYLEX argument, REV argument and LIMIT' => [
                [3, 'member1', 2, 'member2', 1, 'member3'],
                3,
                0,
                'byscore',
                true,
                true,
                0,
                2,
                2,
                ['member2', 'member1'],
            ],
        ];
    }

    public function unexpectedValuesProvider(): array
    {
        return [
            'wrong BY argument value' => [
                0, -1, 'wrong value', false, false, 0, 0, 'By argument accepts only "bylex" and "byscore" values',
            ],
            'wrong REV argument type' => [
                0, -1, false, 'wrong value', false, 0, 0, 'Wrong rev argument type',
            ],
            'wrong LIMIT argument type' => [
                0, -1, false, false, 'wrong value', 0, 0, 'Wrong limit argument type',
            ],
        ];
    }
}
