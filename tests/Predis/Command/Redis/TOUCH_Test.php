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
 * @group realm-server
 */
class TOUCH_Test extends PredisCommandTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getExpectedCommand(): string
    {
        return 'Predis\Command\Redis\TOUCH';
    }

    /**
     * {@inheritdoc}
     */
    protected function getExpectedId(): string
    {
        return 'TOUCH';
    }

    /**
     * @requiresRedisVersion >= 3.2.1
     *
     * @group disconnected
     */
    public function testFilterArguments(): void
    {
        $arguments = ['key1', 'key2', 'key3'];
        $expected = ['key1', 'key2', 'key3'];

        $command = $this->getCommand();
        $command->setArguments($arguments);
        $this->assertSame($expected, $command->getArguments());
    }

    /**
     * @requiresRedisVersion >= 3.2.1
     *
     * @group disconnected
     */
    public function testFilterArgumentsAsSingleArray(): void
    {
        $arguments = [['key1', 'key2', 'key3']];
        $expected = ['key1', 'key2', 'key3'];

        $command = $this->getCommand();
        $command->setArguments($arguments);

        $this->assertSame($expected, $command->getArguments());
    }

    /**
     * @requiresRedisVersion >= 3.2.1
     *
     * @group disconnected
     */
    public function testParseResponse(): void
    {
        $command = $this->getCommand();

        $this->assertSame(10, $command->parseResponse(10));
    }

    /**
     * @requiresRedisVersion >= 3.2.1
     *
     * @group connected
     */
    public function testReturnsNumberOfDeletedKeys(): void
    {
        $redis = $this->getClient();

        $this->assertSame(0, $redis->touch('foo'));

        $redis->set('foo', 'bar');
        $this->assertSame(1, $redis->touch('foo'));
        $this->assertSame(1, $redis->touch('foo', 'hoge'));

        $redis->set('hoge', 'piyo');
        $this->assertSame(1, $redis->touch('foo'));
        $this->assertSame(2, $redis->touch('foo', 'hoge'));
    }
}
