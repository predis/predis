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

class BLMOVE_Test extends PredisCommandTestCase
{
    /**
     * {@inheritDoc}
     */
    protected function getExpectedCommand(): string
    {
        return BLMOVE::class;
    }

    /**
     * {@inheritDoc}
     */
    protected function getExpectedId(): string
    {
        return 'BLMOVE';
    }

    /**
     * @group disconnected
     */
    public function testFilterArguments(): void
    {
        $arguments = ['list', 'argument1', 'argument2', 'argument3'];
        $expected = ['list', 'argument1', 'argument2', 'argument3'];

        $command = $this->getCommand();
        $command->setArguments($arguments);

        $this->assertSame($expected, $command->getArguments());
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
     * @dataProvider listsProvider
     * @param  array  $firstList
     * @param  array  $secondList
     * @param  string $where
     * @param  string $to
     * @param  string $expectedResponse
     * @param  array  $expectedModifiedFirstList
     * @param  array  $expectedModifiedSecondList
     * @return void
     * @requiresRedisVersion >= 6.2.0
     */
    public function testReturnsCorrectListElement(
        array $firstList,
        array $secondList,
        string $where,
        string $to,
        string $expectedResponse,
        array $expectedModifiedFirstList,
        array $expectedModifiedSecondList
    ): void {
        $redis = $this->getClient();

        $redis->rpush('test-blmove1', $firstList);
        $redis->rpush('test-blmove2', $secondList);

        $actualResponse = $redis->blmove('test-blmove1', 'test-blmove2', $where, $to, 0);

        $this->assertSame($expectedResponse, $actualResponse);
        $this->assertSame($expectedModifiedFirstList, $redis->lrange('test-blmove1', 0, -1));
        $this->assertSame($expectedModifiedSecondList, $redis->lrange('test-blmove2', 0, -1));
    }

    /**
     * @group connected
     * @dataProvider sameListProvider
     * @param  array  $list
     * @param  string $where
     * @param  string $to
     * @param  string $expectedResponse
     * @param  array  $expectedModifiedList
     * @return void
     * @requiresRedisVersion >= 6.2.0
     */
    public function testReturnsCorrectListElementAndListRotationPerformedOnTheSameListOperation(
        array $list,
        string $where,
        string $to,
        string $expectedResponse,
        array $expectedModifiedList
    ): void {
        $redis = $this->getClient();

        $redis->rpush('test-blmove1', $list);

        $actualResponse = $redis->blmove('test-blmove1', 'test-blmove1', $where, $to, 0);

        $this->assertSame($expectedResponse, $actualResponse);
        $this->assertSame($expectedModifiedList, $redis->lrange('test-blmove1', 0, -1));
    }

    /**
     * @group connected
     * @return void
     * @requiresRedisVersion >= 6.2.0
     */
    public function testThrowsExceptionOnWrongType(): void
    {
        $this->expectException(ServerException::class);
        $this->expectExceptionMessage('Operation against a key holding the wrong kind of value');

        $redis = $this->getClient();

        $redis->set('foo', 'bar');
        $redis->blmove('foo', 'test-lmove', 'LEFT', 'LEFT', 0);
    }

    public function listsProvider(): array
    {
        return [
            'move first element from list into head of another list' => [
                ['element1', 'element2', 'element3'],
                ['element4', 'element5', 'element6'],
                'LEFT',
                'LEFT',
                'element1',
                ['element2', 'element3'],
                ['element1', 'element4', 'element5', 'element6'],
            ],
            'move first element from list into tail of another list' => [
                ['element1', 'element2', 'element3'],
                ['element4', 'element5', 'element6'],
                'LEFT',
                'RIGHT',
                'element1',
                ['element2', 'element3'],
                ['element4', 'element5', 'element6', 'element1'],
            ],
            'move last element from list into head of another list' => [
                ['element1', 'element2', 'element3'],
                ['element4', 'element5', 'element6'],
                'RIGHT',
                'LEFT',
                'element3',
                ['element1', 'element2'],
                ['element3', 'element4', 'element5', 'element6'],
            ],
            'move last element from list into tail of another list' => [
                ['element1', 'element2', 'element3'],
                ['element4', 'element5', 'element6'],
                'RIGHT',
                'RIGHT',
                'element3',
                ['element1', 'element2'],
                ['element4', 'element5', 'element6', 'element3'],
            ],
        ];
    }

    public function sameListProvider(): array
    {
        return [
            'list rotation - head into tail' => [
                ['element1', 'element2', 'element3'],
                'LEFT',
                'RIGHT',
                'element1',
                ['element2', 'element3', 'element1'],
            ],
            'list rotation - tail into head' => [
                ['element1', 'element2', 'element3'],
                'RIGHT',
                'LEFT',
                'element3',
                ['element3', 'element1', 'element2'],
            ],
            'list rotation - head into head' => [
                ['element1', 'element2', 'element3'],
                'LEFT',
                'LEFT',
                'element1',
                ['element1', 'element2', 'element3'],
            ],
            'list rotation - tail into tail' => [
                ['element1', 'element2', 'element3'],
                'RIGHT',
                'RIGHT',
                'element3',
                ['element1', 'element2', 'element3'],
            ],
        ];
    }
}
