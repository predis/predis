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

/**
 * @group commands
 * @group realm-key
 */
class TTL_Test extends PredisCommandTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getExpectedCommand(): string
    {
        return 'Predis\Command\Redis\TTL';
    }

    /**
     * {@inheritdoc}
     */
    protected function getExpectedId(): string
    {
        return 'TTL';
    }

    /**
     * @group disconnected
     */
    public function testFilterArguments(): void
    {
        $arguments = ['key', 10];
        $expected = ['key', 10];

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

        $this->assertSame(100, $command->parseResponse(100));
    }

    /**
     * @group connected
     */
    public function testReturnsTTL(): void
    {
        $redis = $this->getClient();

        $redis->set('foo', 'bar');
        $redis->expire('foo', 10);

        $this->assertSame(10, $redis->ttl('foo'));
    }

    /**
     * @group connected
     */
    public function testReturnsLessThanZeroOnNonExpiringKeys(): void
    {
        $redis = $this->getClient();

        $redis->set('foo', 'bar');
        $this->assertSame(-1, $redis->ttl('foo'));
    }

    /**
     * @group connected
     */
    public function testReturnsLessThanZeroOnNonExistingKeys(): void
    {
        if ($this->isRedisServerVersion('<', '2.8.0')) {
            $this->assertSame(-1, $this->getClient()->ttl('foo'));
        } else {
            $this->assertSame(-2, $this->getClient()->ttl('foo'));
        }
    }
}
