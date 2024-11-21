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
class SCAN_Test extends PredisCommandTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getExpectedCommand(): string
    {
        return 'Predis\Command\Redis\SCAN';
    }

    /**
     * {@inheritdoc}
     */
    protected function getExpectedId(): string
    {
        return 'SCAN';
    }

    /**
     * @group disconnected
     */
    public function testFilterArguments(): void
    {
        $arguments = [0, 'MATCH', 'key:*', 'COUNT', 5];
        $expected = [0, 'MATCH', 'key:*', 'COUNT', 5];

        $command = $this->getCommand();
        $command->setArguments($arguments);

        $this->assertSame($expected, $command->getArguments());
    }

    /**
     * @group disconnected
     */
    public function testFilterArgumentsBasicUsage(): void
    {
        $arguments = [0];
        $expected = [0];

        $command = $this->getCommand();
        $command->setArguments($arguments);

        $this->assertSame($expected, $command->getArguments());
    }

    /**
     * @group disconnected
     */
    public function testFilterArgumentsWithOptionsArray(): void
    {
        $arguments = [0, ['match' => 'key:*', 'count' => 5]];
        $expected = [0, 'MATCH', 'key:*', 'COUNT', 5];

        $command = $this->getCommand();
        $command->setArguments($arguments);

        $this->assertSame($expected, $command->getArguments());
    }

    /**
     * @group disconnected
     */
    public function testParseResponse(): void
    {
        $raw = ['3', ['key:1', 'key:2', 'key:3']];
        $expected = ['3', ['key:1', 'key:2', 'key:3']];

        $command = $this->getCommand();

        $this->assertSame($expected, $command->parseResponse($raw));
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 2.8.0
     */
    public function testScanWithoutMatch(): void
    {
        $kvs = ['key:one' => 'one', 'key:two' => 'two', 'key:three' => 'three', 'key:four' => 'four'];

        $redis = $this->getClient();
        $redis->mset($kvs);

        $response = $redis->scan(0);

        $this->assertSameValues(array_keys($kvs), $response[1]);
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 2.8.0
     */
    public function testScanWithMatchingKeys(): void
    {
        $kvs = ['key:one' => 'one', 'key:two' => 'two', 'key:three' => 'three', 'key:four' => 'four'];

        $redis = $this->getClient();
        $redis->mset($kvs);

        $response = $redis->scan('0', 'MATCH', 'key:t*');

        $this->assertSameValues(['key:two', 'key:three'], $response[1]);
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 2.8.0
     */
    public function testScanWithNoMatchingKeys(): void
    {
        $kvs = ['key:one' => 'one', 'key:two' => 'two', 'key:three' => 'three', 'key:four' => 'four'];

        $redis = $this->getClient();
        $redis->mset($kvs);

        $response = $redis->scan(0, 'MATCH', 'nokey:*');

        $this->assertSame('0', $response[0]);
        $this->assertEmpty($response[1]);
    }
}
