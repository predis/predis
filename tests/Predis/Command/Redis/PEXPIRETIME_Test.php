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

/**
 * @group commands
 * @group realm-keys
 */
class PEXPIRETIME_Test extends PredisCommandTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getExpectedCommand(): string
    {
        return PEXPIRETIME::class;
    }

    /**
     * {@inheritdoc}
     */
    protected function getExpectedId(): string
    {
        return 'PEXPIRETIME';
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
     * @group connected
     * @return void
     * @requiresRedisVersion >= 7.0.0
     */
    public function testReturnsCorrectExpirationTimeForGivenKey(): void
    {
        $redis = $this->getClient();
        $expirationTime = time() * 1000 + 1000;

        $redis->set('key', 'value');
        $redis->set('key1', 'value');
        $redis->pexpireat('key', $expirationTime);

        $this->assertSame($expirationTime, $redis->pexpiretime('key'));
        $this->assertSame(-1, $redis->pexpiretime('key1'));
        $this->assertSame(-2, $redis->pexpiretime('non-existing key'));
    }
}
