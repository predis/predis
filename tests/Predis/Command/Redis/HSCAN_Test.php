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
 * @group realm-hash
 */
class HSCAN_Test extends PredisCommandTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getExpectedCommand(): string
    {
        return 'Predis\Command\Redis\HSCAN';
    }

    /**
     * {@inheritdoc}
     */
    protected function getExpectedId(): string
    {
        return 'HSCAN';
    }

    /**
     * @group disconnected
     */
    public function testFilterArguments(): void
    {
        $arguments = ['key', 0, 'MATCH', 'field:*', 'COUNT', 10];
        $expected = ['key', 0, 'MATCH', 'field:*', 'COUNT', 10];

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
        $arguments = ['key', 0, ['match' => 'field:*', 'count' => 10]];
        $expected = ['key', 0, 'MATCH', 'field:*', 'COUNT', 10];

        $command = $this->getCommand();
        $command->setArguments($arguments);

        $this->assertSame($expected, $command->getArguments());
    }

    /**
     * @group disconnected
     */
    public function testParseResponse(): void
    {
        $raw = ['3', ['field:1', '1', 'field:2', '2', 'field:3', '3']];
        $expected = ['3', ['field:1' => '1', 'field:2' => '2', 'field:3' => '3']];

        $command = $this->getCommand();
        $command->setArguments($raw);

        $this->assertSame($expected, $command->parseResponse($raw));
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 2.8.0
     */
    public function testScanWithoutMatch(): void
    {
        $expectedFields = ['field:one', 'field:two', 'field:three', 'field:four'];
        $expectedValues = ['one', 'two', 'three', 'four'];

        $redis = $this->getClient();
        $redis->hmset('key', array_combine($expectedFields, $expectedValues));

        $response = $redis->hscan('key', 0);

        $this->assertSame('0', $response[0]);
        $this->assertSame($expectedFields, array_keys($response[1]));
        $this->assertSame($expectedValues, array_values($response[1]));
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 2.8.0
     */
    public function testScanWithMatchingMembers(): void
    {
        $redis = $this->getClient();
        $redis->hmset('key', ['field:one' => 'one', 'field:two' => 'two', 'field:three' => 'three', 'field:four' => 'four']);

        $response = $redis->hscan('key', 0, 'MATCH', 'field:t*');

        $this->assertSame(['field:two', 'field:three'], array_keys($response[1]));
        $this->assertSame(['two', 'three'], array_values($response[1]));
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 7.3.0
     */
    public function testScanWithNoValues(): void
    {
        $redis = $this->getClient();
        $redis->hmset('key', ['field:one' => 'one', 'field:two' => 'two', 'field:three' => 'three', 'field:four' => 'four']);

        $response = $redis->hscan('key', 0, ['MATCH' => 'field:t*', 'NOVALUES' => true]);

        $this->assertSame(['field:two', 'field:three'], $response[1]);
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 2.8.0
     */
    public function testScanWithNoMatchingMembers(): void
    {
        $redis = $this->getClient();
        $redis->hmset('key', ['field:one' => 'one', 'field:two' => 'two', 'field:three' => 'three', 'field:four' => 'four']);

        $response = $redis->hscan('key', 0, 'MATCH', 'nofield:*');

        $this->assertSame('0', $response[0]);
        $this->assertEmpty($response[1]);
    }
}
