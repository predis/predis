<?php

/*
 * This file is part of the Predis package.
 *
 * (c) 2009-2020 Daniele Alessandri
 * (c) 2021-2026 Till Krüss
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Connection;

use PHPUnit\Framework\MockObject\MockObject;
use Predis\ClientException;
use Predis\Command\RawCommand;
use Predis\NotSupportedException;
use Predis\Response\Error as ErrorResponse;
use Predis\Response\ErrorInterface as ErrorResponseInterface;
use PredisTestCase;
use ReflectionClass;
use Relay\Exception as RelayException;
use Relay\Relay;

/**
 * @group ext-relay
 * @requires extension relay
 */
class RelayConnectionTest extends PredisTestCase
{
    /**
     * @var Relay
     */
    private $mockClient;

    /**
     * @var ParametersInterface
     */
    private $parameters;

    /**
     * @var RelayConnection
     */
    private $connection;

    protected function setUp(): void
    {
        $this->mockClient = $this->getMockBuilder(Relay::class)->getMock();
        $this->parameters = new Parameters();

        $this->connection = new RelayConnection($this->parameters, $this->mockClient);
    }

    /**
     * @group disconnected
     * @return void
     */
    public function testIsConnected(): void
    {
        $this->mockClient
            ->expects($this->once())
            ->method('isConnected')
            ->withAnyParameters()
            ->willReturn(true);

        $this->assertTrue($this->connection->isConnected());
    }

    /**
     * @group disconnected
     */
    public function testReadThrowsException(): void
    {
        $this->expectException(NotSupportedException::class);
        $this->expectExceptionMessage('The "relay" extension does not support reading responses.');

        $this->connection->read();
    }

    /**
     * @group disconnected
     */
    public function testWriteThrowsException(): void
    {
        $this->expectException(NotSupportedException::class);
        $this->expectExceptionMessage('The "relay" extension does not support writing operations.');

        $this->connection->write('foobar');
    }

    /**
     * @group disconnected
     * @return void
     */
    public function testDisconnect(): void
    {
        $this->mockClient
            ->expects($this->once())
            ->method('isConnected')
            ->withAnyParameters()
            ->willReturn(true);

        $this->mockClient
            ->expects($this->once())
            ->method('close')
            ->withAnyParameters();

        $this->connection->disconnect();
    }

    /**
     * @group disconnected
     * @return void
     */
    public function testGetClient(): void
    {
        $this->assertSame($this->mockClient, $this->connection->getClient());
    }

    /**
     * @group disconnected
     * @return void
     */
    public function testExecuteCommandOnAlreadyConnectedClient(): void
    {
        $this->mockClient
            ->expects($this->once())
            ->method('isConnected')
            ->withAnyParameters()
            ->willReturn(true);

        $this->mockClient
            ->expects($this->once())
            ->method('rawCommand')
            ->with('GET', 'foo')
            ->willReturn('bar');

        $response = $this->connection->executeCommand(new RawCommand('GET', ['foo']));

        $this->assertSame('bar', $response);
    }

    /**
     * @group disconnected
     * @return void
     */
    public function testExecuteAtypicalCommandOnAlreadyConnectedClient(): void
    {
        $this->mockClient
            ->expects($this->once())
            ->method('isConnected')
            ->withAnyParameters()
            ->willReturn(true);

        $this->mockClient
            ->expects($this->once())
            ->method('AUTH')
            ->with('foo', 'bar')
            ->willReturn(true);

        $response = $this->connection->executeCommand(new RawCommand('AUTH', ['foo', 'bar']));

        $this->assertTrue($response);
    }

    /**
     * @group disconnected
     * @return void
     */
    public function testExecuteCommandOnNonConnectedClient(): void
    {
        $this->mockClient
            ->expects($this->exactly(2))
            ->method('isConnected')
            ->withAnyParameters()
            ->willReturn(false);

        $this->mockClient
            ->expects($this->once())
            ->method('connect')
            ->with($this->parameters->host, $this->parameters->port);

        $this->mockClient
            ->expects($this->once())
            ->method('rawCommand')
            ->with('GET', 'foo')
            ->willReturn('bar');

        $response = $this->connection->executeCommand(new RawCommand('GET', ['foo']));

        $this->assertSame('bar', $response);
    }

    /**
     * @group disconnected
     * @return void
     */
    public function testConnectExecutesOnConnectionCommand(): void
    {
        $this->mockClient
            ->expects($this->exactly(3))
            ->method('isConnected')
            ->withAnyParameters()
            ->willReturn(false);

        $this->mockClient
            ->expects($this->once())
            ->method('connect')
            ->with($this->parameters->host, $this->parameters->port);

        $this->mockClient
            ->expects($this->exactly(2))
            ->method('rawCommand')
            ->withConsecutive(['GET', 'foo'], ['GET', 'bar'])
            ->willReturnOnConsecutiveCalls('baz', 'bad');

        $this->connection->addConnectCommand(new RawCommand('GET', ['foo']));
        $this->connection->addConnectCommand(new RawCommand('GET', ['bar']));

        $this->connection->connect();
    }

    /**
     * @group disconnected
     * @return void
     */
    public function testConnectThrowsExceptionOnErrorResponse(): void
    {
        $this->mockClient
            ->expects($this->exactly(4))
            ->method('isConnected')
            ->withAnyParameters()
            ->willReturn(false);

        $this->mockClient
            ->expects($this->once())
            ->method('connect')
            ->with($this->parameters->host, $this->parameters->port);

        $this->mockClient
            ->expects($this->exactly(2))
            ->method('rawCommand')
            ->withConsecutive(['GET', 'foo'], ['GET', 'bar'])
            ->willReturnOnConsecutiveCalls('baz', new ErrorResponse('FooBar'));

        $this->connection->addConnectCommand(new RawCommand('GET', ['foo']));
        $this->connection->addConnectCommand(new RawCommand('GET', ['bar']));

        $this->expectException(ConnectionException::class);
        $this->expectExceptionMessage('`GET` failed: FooBar [tcp://127.0.0.1:6379]');

        $this->connection->connect();
    }

    /**
     * @group disconnected
     * @return void
     */
    public function testConnectDoNotExecuteCommands(): void
    {
        $this->mockClient
            ->expects($this->once())
            ->method('isConnected')
            ->withAnyParameters()
            ->willReturn(false);

        $this->mockClient
            ->expects($this->once())
            ->method('connect')
            ->with($this->parameters->host, $this->parameters->port);

        $this->mockClient
            ->expects($this->never())
            ->method('rawCommand')
            ->withAnyParameters();

        $this->connection->connect();
    }

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
        $relayMock = $this->getMockBuilder(Relay::class)->getMock();

        $cmdSelect = RawCommand::create('SELECT', '1000');

        /** @var NodeConnectionInterface|MockObject */
        $connection = $this
            ->getMockBuilder($this->getConnectionClass())
            ->onlyMethods(['executeCommand', 'createResource'])
            ->setConstructorArgs([new Parameters(), $relayMock])
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
            ->onlyMethods(['createResource'])
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
            ->onlyMethods(['createResource'])
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
        $connection = new RelayConnection(new Parameters(), new Relay());

        $this->assertFalse($connection->isConnected());
        $connection->getResource();
        $this->assertTrue($connection->isConnected());
    }

    /**
     * @group connected
     */
    public function testExecutesCommand(): void
    {
        $parameters = $this->getParameters();
        $connection = new RelayConnection($parameters, new Relay());
        $connection->executeCommand(new RawCommand('AUTH', [$parameters->password]));

        $this->assertEquals(
            'OK',
            $connection->executeCommand(new RawCommand('SET', ['key', 'value']))
        );

        $this->assertEquals(
            'value',
            $connection->executeCommand(new RawCommand('GET', ['key']))
        );
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 7.2.0
     */
    public function testConnectWithOnConnectionCommands(): void
    {
        $parameters = $this->getParameters();
        $connection = new RelayConnection($parameters, new Relay());
        $connection->addConnectCommand(new RawCommand('AUTH', [$parameters->password]));
        $connection->addConnectCommand(new RawCommand('CLIENT', ['SETNAME', 'predis']));

        $connection->connect();

        $response = $connection->executeCommand(new RawCommand('CLIENT', ['GETNAME']));
        $this->assertSame('predis', $response);
    }
}
