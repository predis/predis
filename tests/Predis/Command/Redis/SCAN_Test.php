<?php

/*
 * This file is part of the Predis package.
 *
 * (c) Daniele Alessandri <suppakilla@gmail.com>
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
        $arguments = array(0, 'MATCH', 'key:*', 'COUNT', 5);
        $expected = array(0, 'MATCH', 'key:*', 'COUNT', 5);

        $command = $this->getCommand();
        $command->setArguments($arguments);

        $this->assertSame($expected, $command->getArguments());
    }

    /**
     * @group disconnected
     */
    public function testFilterArgumentsBasicUsage(): void
    {
        $arguments = array(0);
        $expected = array(0);

        $command = $this->getCommand();
        $command->setArguments($arguments);

        $this->assertSame($expected, $command->getArguments());
    }

    /**
     * @group disconnected
     */
    public function testFilterArgumentsWithOptionsArray(): void
    {
        $arguments = array(0, array('match' => 'key:*', 'count' => 5));
        $expected = array(0, 'MATCH', 'key:*', 'COUNT', 5);

        $command = $this->getCommand();
        $command->setArguments($arguments);

        $this->assertSame($expected, $command->getArguments());
    }

    /**
     * @group disconnected
     */
    public function testParseResponse(): void
    {
        $raw = array('3', array('key:1', 'key:2', 'key:3'));
        $expected = array('3', array('key:1', 'key:2', 'key:3'));

        $command = $this->getCommand();

        $this->assertSame($expected, $command->parseResponse($raw));
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 2.8.0
     */
    public function testScanWithoutMatch(): void
    {
        $kvs = array('key:one' => 'one', 'key:two' => 'two', 'key:three' => 'three', 'key:four' => 'four');

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
        $kvs = array('key:one' => 'one', 'key:two' => 'two', 'key:three' => 'three', 'key:four' => 'four');

        $redis = $this->getClient();
        $redis->mset($kvs);

        $response = $redis->scan('0', 'MATCH', 'key:t*');

        $this->assertSameValues(array('key:two', 'key:three'), $response[1]);
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 2.8.0
     */
    public function testScanWithNoMatchingKeys(): void
    {
        $kvs = array('key:one' => 'one', 'key:two' => 'two', 'key:three' => 'three', 'key:four' => 'four');

        $redis = $this->getClient();
        $redis->mset($kvs);

        $response = $redis->scan(0, 'MATCH', 'nokey:*');

        $this->assertSame('0', $response[0]);
        $this->assertEmpty($response[1]);
    }
}
