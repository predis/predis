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
 * @group realm-key
 */
class RANDOMKEY_Test extends PredisCommandTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getExpectedCommand(): string
    {
        return 'Predis\Command\Redis\RANDOMKEY';
    }

    /**
     * {@inheritdoc}
     */
    protected function getExpectedId(): string
    {
        return 'RANDOMKEY';
    }

    /**
     * @group disconnected
     */
    public function testFilterArguments(): void
    {
        $arguments = [];
        $expected = [];

        $command = $this->getCommand();
        $command->setArguments($arguments);

        $this->assertSame($expected, $command->getArguments());
    }

    /**
     * @group disconnected
     */
    public function testParseResponse(): void
    {
        $raw = 'key';
        $expected = 'key';

        $command = $this->getCommand();

        $this->assertSame($expected, $command->parseResponse($raw));
    }

    /**
     * @group connected
     */
    public function testReturnsZeroOnNonExpiringKeys(): void
    {
        $keys = ['key:1' => 1, 'key:2' => 2, 'key:3' => 3];

        $redis = $this->getClient();
        $redis->mset($keys);

        $this->assertContains($redis->randomkey(), array_keys($keys));
    }

    /**
     * @group connected
     */
    public function testReturnsNullOnEmptyDatabase(): void
    {
        $redis = $this->getClient();

        $this->assertNull($redis->randomkey());
    }
}
