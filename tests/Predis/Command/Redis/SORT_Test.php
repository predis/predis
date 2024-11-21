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

use Predis\Client;

/**
 * @group commands
 * @group realm-key
 */
class SORT_Test extends PredisCommandTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getExpectedCommand(): string
    {
        return 'Predis\Command\Redis\SORT';
    }

    /**
     * {@inheritdoc}
     */
    protected function getExpectedId(): string
    {
        return 'SORT';
    }

    /**
     * Utility method to to an LPUSH of some unordered values on a key.
     *
     * @param Client $redis Redis client instance.
     * @param string $key   Target key
     *
     * @return array
     */
    protected function lpushUnorderedList(Client $redis, $key)
    {
        $list = [2, 100, 3, 1, 30, 10];
        $redis->lpush($key, $list);

        return $list;
    }

    /**
     * @group disconnected
     */
    public function testFilterArguments(): void
    {
        $modifiers = [
            'by' => 'by_key_*',
            'limit' => [1, 4],
            'get' => ['object_*', '#'],
            'sort' => 'asc',
            'alpha' => true,
            'store' => 'destination_key',
        ];
        $arguments = ['key', $modifiers];

        $expected = [
            'key', 'BY', 'by_key_*', 'GET', 'object_*', 'GET', '#',
            'LIMIT', 1, 4, 'ASC', 'ALPHA', 'STORE', 'destination_key',
        ];

        $command = $this->getCommand();
        $command->setArguments($arguments);

        $this->assertEquals($expected, $command->getArguments());
    }

    /**
     * @group disconnected
     */
    public function testGetModifierCanBeString(): void
    {
        $arguments = ['key', ['get' => '#']];
        $expected = ['key', 'GET', '#'];

        $command = $this->getCommand();
        $command->setArguments($arguments);

        $this->assertSame($expected, $command->getArguments());
    }

    /**
     * @group disconnected
     */
    public function testParseResponse(): void
    {
        $raw = ['value1', 'value2', 'value3'];
        $expected = ['value1', 'value2', 'value3'];

        $command = $this->getCommand();

        $this->assertSame($expected, $command->parseResponse($raw));
    }

    /**
     * @group connected
     */
    public function testBasicSort(): void
    {
        $redis = $this->getClient();
        $redis->lpush('list:unordered', $unordered = [2, 100, 3, 1, 30, 10]);

        $this->assertEquals([1, 2, 3, 10, 30, 100], $redis->sort('list:unordered'));
    }

    /**
     * @group connected
     */
    public function testSortWithAscOrDescModifier(): void
    {
        $redis = $this->getClient();
        $redis->lpush('list:unordered', $unordered = [2, 100, 3, 1, 30, 10]);

        $this->assertEquals(
            [100, 30, 10, 3, 2, 1],
            $redis->sort('list:unordered', [
                'sort' => 'desc',
            ])
        );

        $this->assertEquals(
            [1, 2, 3, 10, 30, 100],
            $redis->sort('list:unordered', [
                'sort' => 'asc',
            ])
        );
    }

    /**
     * @group connected
     */
    public function testSortWithLimitModifier(): void
    {
        $redis = $this->getClient();
        $redis->lpush('list:unordered', $unordered = [2, 100, 3, 1, 30, 10]);

        $this->assertEquals(
            [1, 2, 3],
            $redis->sort('list:unordered', [
                'limit' => [0, 3],
            ])
        );

        $this->assertEquals(
            [10, 30],
            $redis->sort('list:unordered', [
                'limit' => [3, 2],
            ])
        );
    }

    /**
     * @group connected
     */
    public function testSortWithAlphaModifier(): void
    {
        $redis = $this->getClient();
        $redis->lpush('list:unordered', $unordered = [2, 100, 3, 1, 30, 10]);

        $this->assertEquals(
            [1, 10, 100, 2, 3, 30],
            $redis->sort('list:unordered', [
                'alpha' => true,
            ])
        );
    }

    /**
     * @group connected
     */
    public function testSortWithStoreModifier(): void
    {
        $redis = $this->getClient();
        $redis->lpush('list:unordered', $unordered = [2, 100, 3, 1, 30, 10]);

        $this->assertCount(
            $redis->sort('list:unordered', [
                'store' => 'list:ordered',
            ]),
            $unordered
        );

        $this->assertEquals([1, 2, 3, 10, 30, 100], $redis->lrange('list:ordered', 0, -1));
    }

    /**
     * @group connected
     */
    public function testSortWithCombinedModifiers(): void
    {
        $redis = $this->getClient();
        $redis->lpush('list:unordered', $unordered = [2, 100, 3, 1, 30, 10]);

        $this->assertEquals(
            [30, 10, 3, 2],
            $redis->sort('list:unordered', [
                'alpha' => false,
                'sort' => 'desc',
                'limit' => [1, 4],
            ])
        );
    }

    /**
     * @group connected
     */
    public function testSortWithGetModifiers(): void
    {
        $redis = $this->getClient();
        $redis->lpush('list:unordered', $unordered = [2, 100, 3, 1, 30, 10]);

        $redis->rpush('list:uids', $uids = [1003, 1001, 1002, 1000]);
        $redis->mset($sortget = [
            'uid:1000' => 'foo',  'uid:1001' => 'bar',
            'uid:1002' => 'hoge', 'uid:1003' => 'piyo',
        ]);

        $this->assertEquals(array_values($sortget), $redis->sort('list:uids', ['get' => 'uid:*']));
    }

    /**
     * @group connected
     */
    public function testThrowsExceptionOnWrongType(): void
    {
        $this->expectException('Predis\Response\ServerException');
        $this->expectExceptionMessage('Operation against a key holding the wrong kind of value');

        $redis = $this->getClient();

        $redis->set('foo', 'bar');
        $redis->sort('foo');
    }
}
