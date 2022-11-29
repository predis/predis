<?php

namespace Predis\Command\Redis;

class ZRANGESTORE_Test extends PredisCommandTestCase
{
    /**
     * @inheritDoc
     */
    protected function getExpectedCommand(): string
    {
        return ZRANGESTORE::class;
    }

    /**
     * @inheritDoc
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
     * @dataProvider rangesProvider
     * @param array $actualSortedSet
     * @param int|string $min
     * @param int|string $max
     * @param string|bool $by
     * @param bool $rev
     * @param bool $limit
     * @param int $offset
     * @param int $count
     * @param int $expectedResultingElements
     * @param array $expectedResponse
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

    public function argumentsProvider(): array
    {
        return [
            'without optional arguments' => [
                ['destination', 'source', 0, -1, false, false, false, 0, 0],
                ['destination', 'source', 0, -1, false, false],
            ],
            'with BYLEX argument' => [
                ['destination', 'source', 0, -1, 'bylex', false, false, 0, 0],
                ['destination', 'source', 0, -1, 'BYLEX', false],
            ],
            'with BYSCORE argument' => [
                ['destination', 'source', 0, -1, 'byscore', false, false, 0, 0],
                ['destination', 'source', 0, -1, 'BYSCORE', false],
            ],
            'with REV argument' => [
                ['destination', 'source', 0, -1, false, true, false, 0, 0],
                ['destination', 'source', 0, -1, false, 'REV'],
            ],
            'with LIMIT argument' => [
                ['destination', 'source', 0, -1, false, false, true, 0, 1],
                ['destination', 'source', 0, -1, false, false, 'LIMIT', 0, 1],
            ],
            'with BYSCORE/BYLEX argument and REV argument' => [
                ['destination', 'source', 0, -1, 'byscore', true, false, 0, 0],
                ['destination', 'source', 0, -1, 'BYSCORE', 'REV'],
            ],
            'with BYSCORE/BYLEX argument, REV argument and LIMIT' => [
                ['destination', 'source', 0, -1, 'bylex', true, true, 0, 1],
                ['destination', 'source', 0, -1, 'BYLEX', 'REV', 'LIMIT', 0, 1],
            ]
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
            ]
        ];
    }
}
