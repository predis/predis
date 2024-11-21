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

/**
 * @group commands
 * @group realm-stream
 */
class XADD_Test extends PredisCommandTestCase
{
    /**
     * @group disconnected
     * @dataProvider dataFilterArguments
     */
    public function testFilterArguments(array $arguments, array $expected): void
    {
        $command = $this->getCommand();
        $command->setArguments($arguments);

        $this->assertSame($expected, $command->getArguments());
    }

    public function dataFilterArguments(): array
    {
        $data = [];
        $data[] = [
            [
                'stream',
                ['key' => 'val'],
                '*',
                ['trim' => ['MINID', '~', '0-1'], 'limit' => 5, 'nomkstream' => true],
            ],
            ['stream', 'NOMKSTREAM', 'MINID', '~', '0-1', 'LIMIT', 5, '*', 'key', 'val'],
        ];

        $data[] = [
            [
                'stream',
                ['key1' => 'val1', 'key2' => 'val2'],
                '*',
                ['trim' => ['MINID', '~', '0-1'], 'limit' => 5, 'nomkstream' => true],
            ],
            ['stream', 'NOMKSTREAM', 'MINID', '~', '0-1', 'LIMIT', 5, '*', 'key1', 'val1', 'key2', 'val2'],
        ];

        $data[] = [
            [
                'stream',
                ['key' => 'val'],
                '*',
                ['trim' => ['MINID', '~', '0-1'], 'limit' => 5],
            ],
            ['stream', 'MINID', '~', '0-1', 'LIMIT', 5, '*', 'key', 'val'],
        ];

        $data[] = [
            [
                'stream',
                ['key' => 'val'],
                '*',
                ['trim' => ['MINID', '~', '0-1']],
            ],
            ['stream', 'MINID', '~', '0-1', '*', 'key', 'val'],
        ];

        $data[] = [
            [
                'stream',
                ['key' => 'val'],
                '*',
                ['trim' => ['MINID', '0-1']],
            ],
            ['stream', 'MINID', '0-1', '*', 'key', 'val'],
        ];

        $data[] = [
            ['stream', ['key' => 'val'], '2-3'],
            ['stream', '2-3', 'key', 'val'],
        ];

        $data[] = [
            ['stream', ['key' => 'val']],
            ['stream', '*', 'key', 'val'],
        ];

        return $data;
    }

    /**
     * {@inheritdoc}
     */
    protected function getExpectedCommand(): string
    {
        return 'Predis\Command\Redis\XADD';
    }

    /**
     * {@inheritdoc}
     */
    protected function getExpectedId(): string
    {
        return 'XADD';
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 5.0.0
     */
    public function testAddsToStreamWithDefaults(): void
    {
        $redis = $this->getClient();

        $redis->xadd('stream', ['key' => 'val']);

        $this->assertSame(1, $redis->xlen('stream'));
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 5.0.0
     */
    public function testAddsToStreamWithSpecificId(): void
    {
        $redis = $this->getClient();
        $id = time() . '-123';

        $redis->xadd('stream', ['key' => 'val'], $id);

        $response = $redis->xrange('stream', $id, $id);
        $this->assertCount(1, $response);
        $this->assertNotNull($response[$id]);
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 6.2.0
     */
    public function testNomkstreamWhenStreamDoesNotExist(): void
    {
        $redis = $this->getClient();

        $redis->xadd('new-stream', ['key' => 'val'], '*', ['nomkstream' => true]);

        $this->assertSame(0, $redis->exists('new-stream'));
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 6.2.0
     */
    public function testNomkstreamWhenStreamExists(): void
    {
        $redis = $this->getClient();

        $redis->xadd('stream', ['key' => 'val']);

        $redis->xadd('stream', ['key' => 'val'], '*', ['nomkstream' => true]);

        $this->assertSame(1, $redis->exists('stream'));
        $this->assertSame(2, $redis->xlen('stream'));
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 6.2.0
     */
    public function testTrimOnMinidExact(): void
    {
        $redis = $this->getClient();

        $redis->xadd('stream', ['key' => 'val']);
        $id = $redis->xadd('stream', ['key' => 'val']);

        $redis->xadd('stream', ['key' => 'val'], '*', ['trim' => ['MINID', $id]]);

        $this->assertSame(2, $redis->xlen('stream'));
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 6.2.0
     */
    public function testTrimOnMinidInexact(): void
    {
        $redis = $this->getClient();
        $config = $redis->config('get', 'stream-node-max-entries');
        $oldStreamNodeMaxEntries = (int) array_pop($config);
        $redis->config('set', 'stream-node-max-entries', 2);

        $redis->xadd('stream', ['key' => 'val']);
        $redis->xadd('stream', ['key' => 'val']);
        $redis->xadd('stream', ['key' => 'val']);
        $id = $redis->xadd('stream', ['key' => 'val']);

        $redis->xadd('stream', ['key' => 'val'], '*', ['trim' => ['MINID', '~', $id]]);

        $this->assertSame(3, $redis->xlen('stream'));
        $redis->config('set', 'stream-node-max-entries', $oldStreamNodeMaxEntries);
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 5.0.0
     */
    public function testTrimOnMaxlenExact(): void
    {
        $redis = $this->getClient();

        $redis->xadd('stream', ['key' => 'val']);
        $redis->xadd('stream', ['key' => 'val']);

        $redis->xadd('stream', ['key' => 'val'], '*', ['trim' => ['MAXLEN', 2]]);

        $this->assertSame(2, $redis->xlen('stream'));
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 5.0.0
     */
    public function testTrimOnMaxlenInexact(): void
    {
        $redis = $this->getClient();
        $config = $redis->config('get', 'stream-node-max-entries');
        $oldStreamNodeMaxEntries = (int) array_pop($config);
        $redis->config('set', 'stream-node-max-entries', 2);

        $redis->xadd('stream', ['key' => 'val']);
        $redis->xadd('stream', ['key' => 'val']);
        $redis->xadd('stream', ['key' => 'val']);
        $redis->xadd('stream', ['key' => 'val']);

        $redis->xadd('stream', ['key' => 'val'], '*', ['trim' => ['MAXLEN', '~', 2]]);

        $this->assertSame(3, $redis->xlen('stream'));
        $redis->config('set', 'stream-node-max-entries', $oldStreamNodeMaxEntries);
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 6.2.0
     */
    public function testTrimOnMaxlenWithLimit(): void
    {
        $redis = $this->getClient();
        $config = $redis->config('get', 'stream-node-max-entries');
        $oldStreamNodeMaxEntries = (int) array_pop($config);
        $redis->config('set', 'stream-node-max-entries', 2);

        $redis->xadd('stream', ['key' => 'val']);
        $redis->xadd('stream', ['key' => 'val']);
        $redis->xadd('stream', ['key' => 'val']);
        $redis->xadd('stream', ['key' => 'val']);
        $redis->xadd('stream', ['key' => 'val']);

        $redis->xadd(
            'stream',
            ['key' => 'val'],
            '*',
            ['trim' => ['MAXLEN', '~', 2], 'limit' => 2]
        );

        $this->assertSame(4, $redis->xlen('stream'));
        $redis->config('set', 'stream-node-max-entries', $oldStreamNodeMaxEntries);
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 5.0.0
     */
    public function testThrowsExceptionOnWrongType(): void
    {
        $this->expectException('Predis\Response\ServerException');
        $this->expectExceptionMessage('Operation against a key holding the wrong kind of value');

        $redis = $this->getClient();

        $redis->set('foo', 'bar');
        $redis->xadd('foo', ['key' => 'val']);
    }
}
