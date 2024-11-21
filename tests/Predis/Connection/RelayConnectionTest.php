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

namespace Predis\Connection;

use PHPUnit\Framework\MockObject\MockObject;
use Predis\ClientException;
use Predis\Command\RawCommand;
use Predis\Response\Error as ErrorResponse;
use Predis\Response\ErrorInterface as ErrorResponseInterface;
use ReflectionClass;
use Relay\Exception as RelayException;
use Relay\Relay;

/**
 * @group ext-relay
 * @requires extension relay
 */
class RelayConnectionTest extends PredisConnectionTestCase
{
    /**
     * {@inheritDoc}
     */
    public function getConnectionClass(): string
    {
        return RelayConnection::class;
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
     * @group connected
     */
    public function testGetIdentifierUsesParentGetIdentifier(): void
    {
        $relayMock = $this
            ->getMockBuilder(Relay::class)
            ->onlyMethods(['endpointId'])
            ->getMock();

        $relayMock->method('endpointId')
            ->willThrowException(
                new RelayException('Not Connected')
            );

        /** @var RelayConnection&MockObject $connection */
        $connection = $this
            ->getMockBuilder($this->getConnectionClass())
            ->onlyMethods(['createResource'])
            ->disableOriginalConstructor()
            ->getMock();

        $reflection = new ReflectionClass($connection);
        $propertyClient = $reflection->getProperty('client');
        $propertyClient->setAccessible(true);
        $propertyClient->setValue($connection, $relayMock);
        $propertyParameters = $reflection->getProperty('parameters');
        $propertyParameters->setAccessible(true);
        $propertyParameters->setValue($connection, new Parameters([
            'host' => '127.0.0.1',
            'port' => 6379,
        ]));

        $this->assertEquals('127.0.0.1:6379', $connection->getIdentifier());
    }

    /**
     * @group connected
     */
    public function testGetIdentifierUsesClientEndpointId(): void
    {
        $relayMock = $this
            ->getMockBuilder(Relay::class)
            ->onlyMethods(['endpointId'])
            ->getMock();

        $relayMock->method('endpointId')
            ->willReturn('127.0.0.1:6379');

        /** @var RelayConnection&MockObject $connection */
        $connection = $this
            ->getMockBuilder($this->getConnectionClass())
            ->onlyMethods(['createResource'])
            ->disableOriginalConstructor()
            ->getMock();

        $reflection = new ReflectionClass($connection);
        $propertyClient = $reflection->getProperty('client');
        $propertyClient->setAccessible(true);
        $propertyClient->setValue($connection, $relayMock);

        $this->assertEquals('127.0.0.1:6379', $connection->getIdentifier());
    }

    /**
     * @group connected
     */
    public function testExecuteCommandReturnsErrorResponseWhenItIsThrownByRelay(): void
    {
        $cmdSelect = RawCommand::create('GET', '1');

        $relayMock = $this
            ->getMockBuilder(Relay::class)
            ->onlyMethods(['rawCommand'])
            ->getMock();

        $relayMock->method('rawCommand')
            ->willThrowException(
                new RelayException('RELAY_ERR_REDIS')
            );

        /** @var RelayConnection&MockObject $connection */
        $connection = $this
            ->getMockBuilder($this->getConnectionClass())
            ->onlyMethods(['createResource', 'createClient'])
            ->disableOriginalConstructor()
            ->getMock();

        $reflection = new ReflectionClass($connection);
        $property = $reflection->getProperty('client');
        $property->setAccessible(true);
        $property->setValue($connection, $relayMock);

        $connection->method('createResource');

        $response = $connection->executeCommand($cmdSelect);

        $this->assertInstanceOf(ErrorResponseInterface::class, $response);
    }

    /**
     * @group connected
     */
    public function testExecuteCommandThrowsExceptionWhenThrownByRelayAndItIsNotErrorResponse(): void
    {
        $this->expectException('Predis\ClientException');
        $cmdSelect = RawCommand::create('GET', '1');

        $relayMock = $this
            ->getMockBuilder(Relay::class)
            ->onlyMethods(['rawCommand'])
            ->getMock();

        $relayMock->method('rawCommand')
            ->willThrowException(
                new ClientException('RELAY_ERR_REDIS')
            );

        /** @var RelayConnection&MockObject $connection */
        $connection = $this
            ->getMockBuilder($this->getConnectionClass())
            ->onlyMethods(['createResource', 'createClient'])
            ->disableOriginalConstructor()
            ->getMock();

        $reflection = new ReflectionClass($connection);
        $property = $reflection->getProperty('client');
        $property->setAccessible(true);
        $property->setValue($connection, $relayMock);

        $connection->method('createResource');

        $connection->executeCommand($cmdSelect);
    }

    // ******************************************************************** //
    // ---- INTEGRATION TESTS --------------------------------------------- //
    // ******************************************************************** //

    /**
     * @group connected
     */
    public function testGetResourceForcesConnection(): void
    {
        $connection = $this->createConnection();

        $this->assertFalse($connection->isConnected());
        $connection->getResource();
        $this->assertTrue($connection->isConnected());
    }

    /**
     * @group connected
     * @group slow
     * @requires PHP 5.4
     */
    public function testThrowsExceptionOnReadWriteTimeout(): void
    {
        $this->expectException('Predis\Connection\ConnectionException');

        $connection = $this->createConnectionWithParams([
            'read_write_timeout' => 0.5,
        ], true);

        $connection->executeCommand(
            $this->getCommandFactory()->create('brpop', ['foo', 3])
        );
    }

    /**
     * @medium
     * @group connected
     * @group relay-incompatible
     */
    public function testThrowsExceptionOnProtocolDesynchronizationErrors(): void
    {
        $this->expectException('Predis\Protocol\ProtocolException');

        $connection = $this->createConnection();
        $stream = $connection->getResource();

        $connection->writeRequest($this->getCommandFactory()->create('ping'));
        stream_socket_recvfrom($stream, 1);

        $connection->read();
    }

    /**
     * @group connected
     * @group relay-incompatible
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
     * @group relay-incompatible
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
     * @group relay-incompatible
     * @requires PHP 5.4
     */
    public function testPersistentConnectionsToSameNodeShareResource(): void
    {
        $connection1 = $this->createConnectionWithParams(['persistent' => true]);
        $connection2 = $this->createConnectionWithParams(['persistent' => true]);

        $this->assertPersistentConnection($connection1);
        $this->assertPersistentConnection($connection2);

        $this->assertSame($connection1->getResource(), $connection2->getResource());

        $connection1->disconnect();
    }

    /**
     * @group connected
     * @group relay-incompatible
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
     * @group relay-incompatible
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
     * @group relay-incompatible
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
     * @group relay-incompatible
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
}
