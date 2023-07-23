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
 * @group realm-server
 */
class CLIENT_Test extends PredisCommandTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getExpectedCommand(): string
    {
        return 'Predis\Command\Redis\CLIENT';
    }

    /**
     * {@inheritdoc}
     */
    protected function getExpectedId(): string
    {
        return 'CLIENT';
    }

    /**
     * @group disconnected
     */
    public function testFilterArgumentsOfClientKill(): void
    {
        $arguments = ['kill', '127.0.0.1:45393'];
        $expected = ['kill', '127.0.0.1:45393'];

        $command = $this->getCommand();
        $command->setArguments($arguments);

        $this->assertSame($expected, $command->getArguments());
    }

    /**
     * @group disconnected
     */
    public function testFilterArgumentsOfClientList(): void
    {
        $arguments = ['list'];
        $expected = ['list'];

        $command = $this->getCommand();
        $command->setArguments($arguments);

        $this->assertSame($expected, $command->getArguments());
    }

    /**
     * @group disconnected
     */
    public function testFilterArgumentsOfClientGetname(): void
    {
        $arguments = $expected = ['getname'];

        $command = $this->getCommand();
        $command->setArguments($arguments);

        $this->assertSame($expected, $command->getArguments());
    }

    /**
     * @group disconnected
     */
    public function testFilterArgumentsOfClientSetname(): void
    {
        $arguments = $expected = ['setname', 'connection-a'];

        $command = $this->getCommand();
        $command->setArguments($arguments);

        $this->assertSame($expected, $command->getArguments());
    }

    /**
     * @group disconnected
     */
    public function testParseResponseOfClientKill(): void
    {
        $command = $this->getCommand();
        $command->setArguments(['kill']);

        $this->assertSame(true, $command->parseResponse(true));
    }

    /**
     * @group disconnected
     */
    public function testParseResponseOfClientList(): void
    {
        $command = $this->getCommand();
        $command->setArguments(['list']);

        $raw = <<<BUFFER
addr=127.0.0.1:45393 fd=6 idle=0 flags=N db=0 sub=0 psub=0
addr=127.0.0.1:45394 fd=7 idle=0 flags=N db=0 sub=0 psub=0
addr=127.0.0.1:45395 fd=8 idle=0 flags=N db=0 sub=0 psub=0

BUFFER;

        $parsed = [
            ['addr' => '127.0.0.1:45393', 'fd' => '6', 'idle' => '0', 'flags' => 'N', 'db' => '0', 'sub' => '0', 'psub' => '0'],
            ['addr' => '127.0.0.1:45394', 'fd' => '7', 'idle' => '0', 'flags' => 'N', 'db' => '0', 'sub' => '0', 'psub' => '0'],
            ['addr' => '127.0.0.1:45395', 'fd' => '8', 'idle' => '0', 'flags' => 'N', 'db' => '0', 'sub' => '0', 'psub' => '0'],
        ];

        $this->assertSame($parsed, $command->parseResponse($raw));
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 2.4.0
     */
    public function testReturnsListOfConnectedClients(): void
    {
        $redis = $this->getClient();

        $this->assertIsArray($clients = $redis->client('LIST'));
        $this->assertGreaterThanOrEqual(1, count($clients));
        $this->assertIsArray($clients[0]);
        $this->assertArrayHasKey('addr', $clients[0]);
        $this->assertArrayHasKey('fd', $clients[0]);
        $this->assertArrayHasKey('idle', $clients[0]);
        $this->assertArrayHasKey('flags', $clients[0]);
        $this->assertArrayHasKey('db', $clients[0]);
        $this->assertArrayHasKey('sub', $clients[0]);
        $this->assertArrayHasKey('psub', $clients[0]);
    }

    /**
     * @group connected
     * @group relay-incompatible
     * @requiresRedisVersion >= 2.6.9
     */
    public function testGetsNameOfConnection(): void
    {
        $redis = $this->getClient();
        $clientName = $redis->client('GETNAME');
        $this->assertNull($clientName);

        $expectedConnectionName = 'foo-bar';
        $this->assertEquals('OK', $redis->client('SETNAME', $expectedConnectionName));
        $this->assertEquals($expectedConnectionName, $redis->client('GETNAME'));
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 2.6.9
     */
    public function testSetsNameOfConnection(): void
    {
        $redis = $this->getClient();

        $expectedConnectionName = 'foo-baz';
        $this->assertEquals('OK', $redis->client('SETNAME', $expectedConnectionName));
        $this->assertEquals($expectedConnectionName, $redis->client('GETNAME'));
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 7.0.0
     */
    public function testSetNoEvictModeForCurrentConnection(): void
    {
        $redis = $this->getClient();

        $this->assertEquals('OK', $redis->client('NO-EVICT', 'ON'));
        $this->assertEquals('OK', $redis->client('NO-EVICT', 'OFF'));
    }

    /**
     * @return array
     */
    public function invalidConnectionNameProvider()
    {
        return [
            ['foo space'],
            ['foo \n'],
            ['foo $'],
        ];
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 2.6.9
     * @dataProvider invalidConnectionNameProvider
     *
     * @param string $invalidConnectionName
     */
    public function testInvalidSetNameOfConnection($invalidConnectionName)
    {
        $this->expectException('Predis\Response\ServerException');

        $redis = $this->getClient();
        $redis->client('SETNAME', $invalidConnectionName);
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 2.4.0
     */
    public function testThrowsExceptioOnWrongModifier(): void
    {
        $this->expectException('Predis\Response\ServerException');

        $redis = $this->getClient();

        $redis->client('FOO');
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 2.4.0
     */
    public function testThrowsExceptionWhenKillingUnknownClient(): void
    {
        $this->expectException('Predis\Response\ServerException');
        $this->expectExceptionMessage('ERR No such client');

        $redis = $this->getClient();

        $redis->client('KILL', '127.0.0.1:65535');
    }
}
