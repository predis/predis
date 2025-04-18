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

namespace Predis\Connection;

use PHPUnit\Framework\MockObject\MockObject;
use Predis\Client;
use Predis\Command\RawCommand;
use Predis\Response\Error as ErrorResponse;

class StreamConnectionTest extends PredisConnectionTestCase
{
    /**
     * {@inheritDoc}
     */
    public function getConnectionClass(): string
    {
        return 'Predis\Connection\StreamConnection';
    }

    /**
     * @group disconnected
     */
    public function testThrowsExceptionOnInitializationCommandFailure(): void
    {
        $this->expectException('Predis\Connection\ConnectionException');
        $this->expectExceptionMessage('`SELECT` failed: ERR invalid DB index [tcp://127.0.0.1:6379]');

        $cmdSelect = RawCommand::create('SELECT', '1000');

        /** @var NodeConnectionInterface|MockObject */
        $connection = $this
            ->getMockBuilder($this->getConnectionClass())
            ->onlyMethods(['executeCommand', 'createResource'])
            ->setConstructorArgs([new Parameters()])
            ->getMock();
        $connection
            ->method('executeCommand')
            ->with($cmdSelect)
            ->willReturn(
                new ErrorResponse('ERR invalid DB index')
            );

        $connection->method('createResource');

        $connection->addConnectCommand($cmdSelect);
        $connection->connect();
    }

    /**
     * @group disconnected
     */
    public function testDoesntThrowErrorOnInvalidResource(): void
    {
        $this->expectException('Predis\Connection\ConnectionException');

        $cmdSelect = RawCommand::create('SELECT', '1000');
        $invalidResource = null;

        /** @var NodeConnectionInterface|MockObject */
        $connection = $this
            ->getMockBuilder($this->getConnectionClass())
            ->onlyMethods(['getResource'])
            ->setConstructorArgs([new Parameters()])
            ->getMock();
        $connection
            ->method('getResource')
            ->willReturn($invalidResource);

        $connection->writeRequest($cmdSelect);
    }

    // ******************************************************************** //
    // ---- INTEGRATION TESTS --------------------------------------------- //
    // ******************************************************************** //

    /**
     * @group connected
     * @requires PHP 5.4
     */
    public function testPersistentParameterWithFalseLikeValues(): void
    {
        $connection1 = $this->createConnectionWithParams(['persistent' => 0]);
        $this->assertNonPersistentConnection($connection1);

        $connection2 = $this->createConnectionWithParams(['persistent' => false]);
        $this->assertNonPersistentConnection($connection2);

        $connection3 = $this->createConnectionWithParams(['persistent' => '0']);
        $this->assertNonPersistentConnection($connection3);

        $connection4 = $this->createConnectionWithParams(['persistent' => 'false']);
        $this->assertNonPersistentConnection($connection4);
    }

    /**
     * @group connected
     * @requires PHP 5.4
     */
    public function testPersistentParameterWithTrueLikeValues(): void
    {
        $connection1 = $this->createConnectionWithParams(['persistent' => 1]);
        $this->assertPersistentConnection($connection1);

        $connection2 = $this->createConnectionWithParams(['persistent' => true]);
        $this->assertPersistentConnection($connection2);

        $connection3 = $this->createConnectionWithParams(['persistent' => '1']);
        $this->assertPersistentConnection($connection3);

        $connection4 = $this->createConnectionWithParams(['persistent' => 'true']);
        $this->assertPersistentConnection($connection4);

        $connection1->disconnect();
    }

    /**
     * @group connected
     * @requires PHP 5.4
     */
    public function testPersistentConnectionsToSameNodeDoNotShareResourceUsingDifferentPersistentID(): void
    {
        $connection1 = $this->createConnectionWithParams(['persistent' => 'conn1']);
        $connection2 = $this->createConnectionWithParams(['persistent' => 'conn2']);

        $this->assertPersistentConnection($connection1);
        $this->assertPersistentConnection($connection2);

        $this->assertNotSame($connection1->getResource(), $connection2->getResource());
    }

    /**
     * @group connected
     */
    public function testTcpNodelayParameterSetsContextFlagWhenTrue()
    {
        $connection = $this->createConnectionWithParams(['tcp_nodelay' => true]);
        $options = stream_context_get_options($connection->getResource());

        $this->assertIsArray($options);
        $this->assertArrayHasKey('socket', $options);
        $this->assertArrayHasKey('tcp_nodelay', $options['socket']);
        $this->assertTrue($options['socket']['tcp_nodelay']);
    }

    /**
     * @group connected
     */
    public function testTcpNodelayParameterDoesNotSetContextFlagWhenFalse()
    {
        $connection = $this->createConnectionWithParams(['tcp_nodelay' => false]);
        $options = stream_context_get_options($connection->getResource());

        $this->assertIsArray($options);
        $this->assertArrayHasKey('socket', $options);
        $this->assertArrayHasKey('tcp_nodelay', $options['socket']);
        $this->assertFalse($options['socket']['tcp_nodelay']);
    }

    /**
     * @group connected
     */
    public function testTcpDelayContextFlagIsNotSetByDefault()
    {
        $connection = $this->createConnectionWithParams([]);
        $options = stream_context_get_options($connection->getResource());

        $this->assertIsArray($options);
        $this->assertArrayHasKey('socket', $options);
        $this->assertArrayHasKey('tcp_nodelay', $options['socket']);
        $this->assertFalse($options['socket']['tcp_nodelay']);
    }

    /**
     * @group connected
     * @requiresRedisVersion < 7.0.0
     */
    public function testConnectDoNotThrowsExceptionOnClientCommandError(): void
    {
        $connection = $this->createConnectionWithParams([]);
        $connection->addConnectCommand(
            new RawCommand('CLIENT', ['SETINFO', 'LIB-NAME', 'predis'])
        );
        $connection->addConnectCommand(
            new RawCommand('CLIENT', ['SETINFO', 'LIB-VER', Client::VERSION])
        );

        $connection->connect();
        $this->assertTrue(true);
    }
}
