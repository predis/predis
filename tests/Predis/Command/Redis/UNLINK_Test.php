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

use Predis\Command\PrefixableCommand;

/**
 * @group commands
 * @group realm-key
 */
class UNLINK_Test extends PredisCommandTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getExpectedCommand(): string
    {
        return 'Predis\Command\Redis\UNLINK';
    }

    /**
     * {@inheritdoc}
     */
    protected function getExpectedId(): string
    {
        return 'UNLINK';
    }

    /**
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
     * @group disconnected
     */
    public function testParseResponse(): void
    {
        $command = $this->getCommand();

        $this->assertSame(10, $command->parseResponse(10));
    }

    /**
     * @group disconnected
     */
    public function testPrefixKeys(): void
    {
        /** @var PrefixableCommand $command */
        $command = $this->getCommand();
        $actualArguments = ['arg1', 'arg2', 'arg3', 'arg4'];
        $prefix = 'prefix:';
        $expectedArguments = ['prefix:arg1', 'prefix:arg2', 'prefix:arg3', 'prefix:arg4'];

        $command->setArguments($actualArguments);
        $command->prefixKeys($prefix);

        $this->assertSame($expectedArguments, $command->getArguments());
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 4.0.0
     */
    public function testReturnsNumberOfUnlinkedKeys(): void
    {
        $redis = $this->getClient();

        $this->assertSame(0, $redis->unlink('foo'));

        $redis->set('foo', 'bar');
        $this->assertSame(1, $redis->unlink('foo'));

        $redis->set('foo', 'bar');
        $this->assertSame(1, $redis->unlink('foo', 'hoge'));

        $redis->set('foo', 'bar');
        $redis->set('hoge', 'piyo');
        $this->assertSame(2, $redis->unlink('foo', 'hoge'));
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 6.0.0
     */
    public function testReturnsNumberOfUnlinkedKeysResp3(): void
    {
        $redis = $this->getResp3Client();

        $this->assertSame(0, $redis->unlink('foo'));

        $redis->set('foo', 'bar');
        $this->assertSame(1, $redis->unlink('foo'));

        $redis->set('foo', 'bar');
        $this->assertSame(1, $redis->unlink('foo', 'hoge'));

        $redis->set('foo', 'bar');
        $redis->set('hoge', 'piyo');
        $this->assertSame(2, $redis->unlink('foo', 'hoge'));
    }
}
