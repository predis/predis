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
 * @group realm-set
 */
class SSCAN_Test extends PredisCommandTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getExpectedCommand(): string
    {
        return 'Predis\Command\Redis\SSCAN';
    }

    /**
     * {@inheritdoc}
     */
    protected function getExpectedId(): string
    {
        return 'SSCAN';
    }

    /**
     * @group disconnected
     */
    public function testFilterArguments(): void
    {
        $arguments = ['key', 0, 'MATCH', 'member:*', 'COUNT', 10];
        $expected = ['key', 0, 'MATCH', 'member:*', 'COUNT', 10];

        $command = $this->getCommand();
        $command->setArguments($arguments);

        $this->assertSame($expected, $command->getArguments());
    }

    /**
     * @group disconnected
     */
    public function testFilterArgumentsBasicUsage(): void
    {
        $arguments = ['key', 0];
        $expected = ['key', 0];

        $command = $this->getCommand();
        $command->setArguments($arguments);

        $this->assertSame($expected, $command->getArguments());
    }

    /**
     * @group disconnected
     */
    public function testFilterArgumentsWithOptionsArray(): void
    {
        $arguments = ['key', 0, ['match' => 'member:*', 'count' => 10]];
        $expected = ['key', 0, 'MATCH', 'member:*', 'COUNT', 10];

        $command = $this->getCommand();
        $command->setArguments($arguments);

        $this->assertSame($expected, $command->getArguments());
    }

    /**
     * @group disconnected
     */
    public function testParseResponse(): void
    {
        $raw = ['3', ['member:1', 'member:2', 'member:3']];
        $expected = ['3', ['member:1', 'member:2', 'member:3']];

        $command = $this->getCommand();

        $this->assertSame($expected, $command->parseResponse($raw));
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 2.8.0
     */
    public function testScanWithoutMatch(): void
    {
        $redis = $this->getClient();
        $redis->sadd('key', $members = ['member:one', 'member:two', 'member:three', 'member:four']);

        $response = $redis->sscan('key', 0);

        $this->assertSame('0', $response[0]);
        $this->assertSameValues($members, $response[1]);
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 2.8.0
     */
    public function testScanWithMatchingMembers(): void
    {
        $redis = $this->getClient();
        $redis->sadd('key', $members = ['member:one', 'member:two', 'member:three', 'member:four']);

        $response = $redis->sscan('key', 0, 'MATCH', 'member:t*');

        $this->assertSameValues(['member:two', 'member:three'], $response[1]);
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 2.8.0
     */
    public function testScanWithNoMatchingMembers(): void
    {
        $redis = $this->getClient();
        $redis->sadd('key', $members = ['member:one', 'member:two', 'member:three', 'member:four']);

        $response = $redis->sscan('key', 0, 'MATCH', 'nomember:*');

        $this->assertSame('0', $response[0]);
        $this->assertEmpty($response[1]);
    }
}
