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
        $arguments = ['KILL', '127.0.0.1:45393'];
        $expected = ['KILL', '127.0.0.1:45393'];

        $command = $this->getCommand();
        $command->setArguments($arguments);

        $this->assertSame($expected, $command->getArguments());
    }

    /**
     * @dataProvider listArgumentsProvider
     * @group disconnected
     */
    public function testFilterArgumentsOfClientList(array $actualArguments, array $expectedArguments): void
    {
        $command = $this->getCommand();
        $command->setArguments($actualArguments);

        $this->assertSame($expectedArguments, $command->getArguments());
    }

    /**
     * @group disconnected
     */
    public function testFilterArgumentsOfClientGetname(): void
    {
        $arguments = $expected = ['GETNAME'];

        $command = $this->getCommand();
        $command->setArguments($arguments);

        $this->assertSame($expected, $command->getArguments());
    }

    /**
     * @group disconnected
     */
    public function testFilterArgumentsOfClientSetname(): void
    {
        $arguments = $expected = ['SETNAME', 'connection-a'];

        $command = $this->getCommand();
        $command->setArguments($arguments);

        $this->assertSame($expected, $command->getArguments());
    }

    /**
     * @dataProvider noTouchArgumentsProvider
     * @group disconnected
     */
    public function testFilterArgumentsNoTouch(array $actualArguments, array $expectedArguments): void
    {
        $command = $this->getCommand();
        $command->setArguments($actualArguments);

        $this->assertSame($expectedArguments, $command->getArguments());
    }

    /**
     * @dataProvider setInfoArgumentsProvider
     * @group disconnected
     */
    public function testFilterArgumentsSetInfo(array $actualArguments, array $expectedArguments): void
    {
        $command = $this->getCommand();
        $command->setArguments($actualArguments);

        $this->assertSame($expectedArguments, $command->getArguments());
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

        $this->assertIsArray($clients = $redis->client->list());
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
     * @requiresRedisVersion >= 6.0.0
     */
    public function testReturnsListOfConnectedClientsResp3(): void
    {
        $redis = $this->getResp3Client();

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
        $clientName = $redis->client->getName();
        $this->assertNull($clientName);

        $expectedConnectionName = 'foo-bar';
        $this->assertEquals('OK', $redis->client->setName($expectedConnectionName));
        $this->assertEquals($expectedConnectionName, $redis->client->getName());
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 6.0.0
     */
    public function testGetsNameOfConnectionResp3(): void
    {
        $redis = $this->getResp3Client();
        $clientName = $redis->client('GETNAME');
        $this->assertSame('predis', $clientName);

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
        $this->assertEquals('OK', $redis->client->setName($expectedConnectionName));
        $this->assertEquals($expectedConnectionName, $redis->client->getName());
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 7.0.0
     */
    public function testNoEvictTurnEnableEvictionMode(): void
    {
        $redis = $this->getClient();

        $this->assertEquals('OK', $redis->client->noEvict(true));
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 7.2.0
     */
    public function testNoTouchTurnOnControlOnKeys(): void
    {
        $redis = $this->getClient();

        $this->assertEquals('OK', $redis->client->noTouch(true));
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 7.2.0
     */
    public function testSetInfoToCurrentClientConnection(): void
    {
        $redis = $this->getClient();

        $this->assertEquals('OK', $redis->client->setInfo('LIB-NAME', 'lib'));
        $this->assertEquals('OK', $redis->client->setInfo('LIB-VER', '1.0.0'));
        $this->assertSame('lib', $redis->client->list()[0]['lib-name']);
        $this->assertSame('1.0.0', $redis->client->list()[0]['lib-ver']);
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
        $redis->client->setName($invalidConnectionName);
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

        $redis->client->kill('127.0.0.1:65535');
    }

    public function listArgumentsProvider(): array
    {
        return [
            'with default arguments' => [
                ['LIST'],
                ['LIST'],
            ],
            'with TYPE modifier' => [
                ['LIST', 'MASTER'],
                ['LIST', 'TYPE', 'MASTER'],
            ],
            'with ID modifier' => [
                ['LIST', null, 1, 2, 3],
                ['LIST', 'ID', 1, 2, 3],
            ],
        ];
    }

    public function noTouchArgumentsProvider(): array
    {
        return [
            'with default arguments' => [
                ['NOTOUCH'],
                ['NO-TOUCH'],
            ],
            'with enabled modifier' => [
                ['NOTOUCH', true],
                ['NO-TOUCH', 'ON'],
            ],
            'with disabled modifier' => [
                ['NOTOUCH', false],
                ['NO-TOUCH', 'OFF'],
            ],
        ];
    }

    public function setInfoArgumentsProvider(): array
    {
        return [
            'with default arguments' => [
                ['SETINFO'],
                ['SETINFO'],
            ],
            'with LIB-NAME modifier' => [
                ['SETINFO', 'LIB-NAME', 'lib'],
                ['SETINFO', 'LIB-NAME', 'lib'],
            ],
            'with LIB-VER modifier' => [
                ['SETINFO', 'LIB-VER', '1.0.0'],
                ['SETINFO', 'LIB-VER', '1.0.0'],
            ],
            'with only modifier given' => [
                ['SETINFO', 'LIB-VER'],
                ['SETINFO'],
            ],
        ];
    }
}
