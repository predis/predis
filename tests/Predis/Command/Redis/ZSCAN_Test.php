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
 * @group realm-zset
 */
class ZSCAN_Test extends PredisCommandTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getExpectedCommand(): string
    {
        return 'Predis\Command\Redis\ZSCAN';
    }

    /**
     * {@inheritdoc}
     */
    protected function getExpectedId(): string
    {
        return 'ZSCAN';
    }

    /**
     * @group disconnected
     */
    public function testFilterArguments(): void
    {
        $arguments = array('key', 0, 'MATCH', 'member:*', 'COUNT', 10);
        $expected = array('key', 0, 'MATCH', 'member:*', 'COUNT', 10);

        $command = $this->getCommand();
        $command->setArguments($arguments);

        $this->assertSame($expected, $command->getArguments());
    }

    /**
     * @group disconnected
     */
    public function testFilterArgumentsBasicUsage(): void
    {
        $arguments = array('key', 0);
        $expected = array('key', 0);

        $command = $this->getCommand();
        $command->setArguments($arguments);

        $this->assertSame($expected, $command->getArguments());
    }

    /**
     * @group disconnected
     */
    public function testFilterArgumentsWithOptionsArray(): void
    {
        $arguments = array('key', 0, array('match' => 'member:*', 'count' => 10));
        $expected = array('key', 0, 'MATCH', 'member:*', 'COUNT', 10);

        $command = $this->getCommand();
        $command->setArguments($arguments);

        $this->assertSame($expected, $command->getArguments());
    }

    /**
     * @group disconnected
     */
    public function testParseResponse(): void
    {
        $raw = array('3', array('member:1', '1', 'member:2', '2', 'member:3', '3'));
        $expected = array('3', array('member:1' => 1.0, 'member:2' => 2.0, 'member:3' => 3.0));

        $command = $this->getCommand();

        $this->assertSame($expected, $command->parseResponse($raw));
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 2.8.0
     */
    public function testScanWithoutMatch(): void
    {
        $expectedMembers = array('member:one', 'member:two', 'member:three', 'member:four');
        $expectedScores = array(1.0, 2.0, 3.0, 4.0);

        $redis = $this->getClient();
        $redis->zadd('key', array_combine($expectedMembers, $expectedScores));

        $response = $redis->zscan('key', 0);

        $this->assertSame('0', $response[0]);
        $this->assertSame($expectedMembers, array_keys($response[1]));
        $this->assertSame($expectedScores, array_values($response[1]));
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 2.8.0
     */
    public function testScanWithMatchingMembers(): void
    {
        $redis = $this->getClient();
        $redis->zadd('key', array('member:one' => 1.0, 'member:two' => 2.0, 'member:three' => 3.0, 'member:four' => 4.0));

        $response = $redis->zscan('key', 0, 'MATCH', 'member:t*');

        $this->assertSame(array('member:two', 'member:three'), array_keys($response[1]));
        $this->assertSame(array(2.0, 3.0), array_values($response[1]));
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 2.8.0
     */
    public function testScanWithNoMatchingMembers(): void
    {
        $redis = $this->getClient();
        $redis->zadd('key', $members = array('member:one' => 1.0, 'member:two' => 2.0, 'member:three' => 3.0, 'member:four' => 4.0));

        $response = $redis->zscan('key', 0, 'MATCH', 'nomember:*');

        $this->assertSame('0', $response[0]);
        $this->assertEmpty($response[1]);
    }
}
