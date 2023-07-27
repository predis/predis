<?php

/*
 * This file is part of the Predis package.
 *
 * (c) 2009-2020 Daniele Alessandri
 * (c) 2021-2023 Till Krüss
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
class PEXPIREAT_Test extends PredisCommandTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getExpectedCommand(): string
    {
        return 'Predis\Command\Redis\PEXPIREAT';
    }

    /**
     * {@inheritdoc}
     */
    protected function getExpectedId(): string
    {
        return 'PEXPIREAT';
    }

    /**
     * @group disconnected
     */
    public function testFilterArguments(): void
    {
        $arguments = ['key', 100];
        $expected = ['key', 100];

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

        $this->assertSame(0, $command->parseResponse(0));
        $this->assertSame(1, $command->parseResponse(1));
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
        $expectedArguments = ['prefix:arg1', 'arg2', 'arg3', 'arg4'];

        $command->setArguments($actualArguments);
        $command->prefixKeys($prefix);

        $this->assertSame($expectedArguments, $command->getArguments());
    }

    /**
     * @medium
     * @group connected
     * @requiresRedisVersion >= 2.6.0
     * @group slow
     */
    public function testCanExpireKeys(): void
    {
        $ttl = 1.5;
        $redis = $this->getClient();

        $redis->set('foo', 'bar');

        $this->assertSame(1, $redis->pexpireat('foo', time() + $ttl * 1000));
        $this->assertLessThan($ttl * 1000, $redis->pttl('foo'));

        $this->sleep($ttl + 0.5);

        $this->assertSame(0, $redis->exists('foo'));
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 2.6.0
     */
    public function testDeletesKeysOnPastUnixTime(): void
    {
        $redis = $this->getClient();

        $redis->set('foo', 'bar');

        $this->assertSame(1, $redis->expireat('foo', time() - 100000));
        $this->assertSame(0, $redis->exists('foo'));
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 6.0.0
     */
    public function testDeletesKeysOnPastUnixTimeResp3(): void
    {
        $redis = $this->getResp3Client();

        $redis->set('foo', 'bar');

        $this->assertSame(1, $redis->expireat('foo', time() - 100000));
        $this->assertSame(0, $redis->exists('foo'));
    }
}
