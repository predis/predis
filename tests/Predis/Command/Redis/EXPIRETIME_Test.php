<?php

/*
 * This file is part of the Predis package.
 *
 * (c) 2009-2020 Daniele Alessandri
 * (c) 2021-2025 Till Krüss
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Command\Redis;

use Predis\Command\PrefixableCommand;

/**
 * @group commands
 * @group realm-keys
 */
class EXPIRETIME_Test extends PredisCommandTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getExpectedCommand(): string
    {
        return EXPIRETIME::class;
    }

    /**
     * {@inheritdoc}
     */
    protected function getExpectedId(): string
    {
        return 'EXPIRETIME';
    }

    /**
     * @group disconnected
     */
    public function testFilterArguments(): void
    {
        $arguments = ['key'];
        $expected = ['key'];

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
     * @group disconnected
     */
    public function testPrefixKeys(): void
    {
        /** @var PrefixableCommand $command */
        $command = $this->getCommand();
        $actualArguments = ['arg1'];
        $prefix = 'prefix:';
        $expectedArguments = ['prefix:arg1'];

        $command->setArguments($actualArguments);
        $command->prefixKeys($prefix);

        $this->assertSame($expectedArguments, $command->getArguments());
    }

    /**
     * @group connected
     * @return void
     * @requiresRedisVersion >= 7.0.0
     */
    public function testReturnsCorrectKeyExpirationTime(): void
    {
        $expirationTime = (int) microtime(true) + 100000;
        $redis = $this->getClient();

        $redis->set('key', 'value');
        $redis->set('key1', 'value');
        $redis->expireat('key', $expirationTime);

        $this->assertSame($expirationTime, $redis->expiretime('key'));
        $this->assertSame(-1, $redis->expiretime('key1'));
        $this->assertSame(-2, $redis->expiretime('non-existing key'));
    }

    /**
     * @group connected
     * @return void
     * @requiresRedisVersion >= 7.0.0
     */
    public function testReturnsCorrectKeyExpirationTimeResp3(): void
    {
        $expirationTime = (int) microtime(true) + 100000;
        $redis = $this->getResp3Client();

        $redis->set('key', 'value');
        $redis->set('key1', 'value');
        $redis->expireat('key', $expirationTime);

        $this->assertSame($expirationTime, $redis->expiretime('key'));
        $this->assertSame(-1, $redis->expiretime('key1'));
        $this->assertSame(-2, $redis->expiretime('non-existing key'));
    }
}
