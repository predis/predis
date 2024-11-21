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
class XTRIM_Test extends PredisCommandTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getExpectedCommand(): string
    {
        return 'Predis\Command\Redis\XTRIM';
    }

    /**
     * {@inheritdoc}
     */
    protected function getExpectedId(): string
    {
        return 'XTRIM';
    }

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
        return [
            [
                ['stream', ['MINID', '~'], '0-1', ['limit' => 10]],
                ['stream', 'MINID', '~', '0-1', 'LIMIT', 10],
            ],
            [
                ['stream', ['MINID'], '0-1', ['limit' => 10]],
                ['stream', 'MINID', '0-1', 'LIMIT', 10],
            ],
            [
                ['stream', 'MINID', '0-1', ['limit' => 10]],
                ['stream', 'MINID', '0-1', 'LIMIT', 10],
            ],
            [
                ['stream', 'MINID', '0-1'],
                ['stream', 'MINID', '0-1'],
            ],
        ];
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
     * @requiresRedisVersion >= 5.0.0
     */
    public function testTrimOnMaxlenExact(): void
    {
        $redis = $this->getClient();

        $redis->xadd('stream', ['key' => 'val']);
        $redis->xadd('stream', ['key' => 'val']);
        $redis->xadd('stream', ['key' => 'val']);

        $res = $redis->xtrim('stream', 'MAXLEN', 2);

        $this->assertSame(1, $res);
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
        $redis->xadd('stream', ['key' => 'val']);

        $res = $redis->xtrim('stream', ['MAXLEN', '~'], 2);

        $this->assertSame(2, $res);
        $this->assertSame(3, $redis->xlen('stream'));
        $redis->config('set', 'stream-node-max-entries', $oldStreamNodeMaxEntries);
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
        $redis->xadd('stream', ['key' => 'val']);

        $res = $redis->xtrim('stream', 'MINID', $id);

        $this->assertSame(1, $res);
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
        $redis->xadd('stream', ['key' => 'val']);

        $res = $redis->xtrim('stream', ['MINID', '~'], $id);

        $this->assertSame(2, $res);
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
        $redis->xadd('stream', ['key' => 'val']);

        $res = $redis->xtrim('stream', ['MAXLEN', '~'], 2, ['limit' => 2]);

        $this->assertSame(2, $res);
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

        $redis->set('key', 'foo');
        $redis->xtrim('key', 'MAXLEN', 2);
    }
}
