<?php

/*
 * This file is part of the Predis package.
 *
 * (c) Daniele Alessandri <suppakilla@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Connection;

use PHPUnit\Framework\MockObject\MockObject;
use Predis\Command\RawCommand;
use Predis\Response\Error as ErrorResponse;

/**
 * @group ext-phpiredis
 * @requires extension phpiredis
 */
class PhpiredisStreamConnectionTest extends PredisConnectionTestCase
{
    /**
     * @inheritDoc
     */
    public function getConnectionClass(): string
    {
        return 'Predis\Connection\PhpiredisStreamConnection';
    }

    /**
     * @group disconnected
     */
    public function testSupportsSchemeTls(): void
    {
        $this->expectException('InvalidArgumentException');
        $this->expectExceptionMessage('SSL encryption is not supported by this connection backend');

        $connection = $this->createConnectionWithParams(array('scheme' => 'tls'));

        $this->assertInstanceOf('Predis\Connection\NodeConnectionInterface', $connection);
    }

    /**
     * @group disconnected
     */
    public function testSupportsSchemeRediss(): void
    {
        $this->expectException('InvalidArgumentException');
        $this->expectExceptionMessage('SSL encryption is not supported by this connection backend');

        $connection = $this->createConnectionWithParams(array('scheme' => 'rediss'));

        $this->assertInstanceOf('Predis\Connection\NodeConnectionInterface', $connection);
    }

    /**
     * @group disconnected
     */
    public function testThrowsExceptionOnInitializationCommandFailure(): void
    {
        $this->expectException('Predis\Connection\ConnectionException');
        $this->expectExceptionMessage("`SELECT` failed: ERR invalid DB index [tcp://127.0.0.1:6379]");

        $cmdSelect = RawCommand::create('SELECT', '1000');

        /** @var NodeConnectionInterface|MockObject */
        $connection = $this
            ->getMockBuilder($this->getConnectionClass())
            ->onlyMethods(array('executeCommand', 'createResource'))
            ->setConstructorArgs(array(new Parameters()))
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

    // ******************************************************************** //
    // ---- INTEGRATION TESTS --------------------------------------------- //
    // ******************************************************************** //

    /**
     * @group connected
     * @group slow
     * @requires PHP 5.4
     */
    public function testThrowsExceptionOnReadWriteTimeout(): void
    {
        $this->expectException('Predis\Connection\ConnectionException');

        $connection = $this->createConnectionWithParams(array(
            'read_write_timeout' => 0.5,
        ), true);

        $connection->executeCommand(
            $this->getCommandFactory()->create('brpop', array('foo', 3))
        );
    }

    /**
     * @medium
     * @group connected
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
     * @requires PHP 5.4
     */
    public function testPersistentParameterWithFalseLikeValues(): void
    {
        $connection1 = $this->createConnectionWithParams(array('persistent' => 0));
        $this->assertNonPersistentConnection($connection1);

        $connection2 = $this->createConnectionWithParams(array('persistent' => false));
        $this->assertNonPersistentConnection($connection2);

        $connection3 = $this->createConnectionWithParams(array('persistent' => '0'));
        $this->assertNonPersistentConnection($connection3);

        $connection4 = $this->createConnectionWithParams(array('persistent' => 'false'));
        $this->assertNonPersistentConnection($connection4);
    }

    /**
     * @group connected
     * @requires PHP 5.4
     */
    public function testPersistentParameterWithTrueLikeValues(): void
    {
        $connection1 = $this->createConnectionWithParams(array('persistent' => 1));
        $this->assertPersistentConnection($connection1);

        $connection2 = $this->createConnectionWithParams(array('persistent' => true));
        $this->assertPersistentConnection($connection2);

        $connection3 = $this->createConnectionWithParams(array('persistent' => '1'));
        $this->assertPersistentConnection($connection3);

        $connection4 = $this->createConnectionWithParams(array('persistent' => 'true'));
        $this->assertPersistentConnection($connection4);

        $connection1->disconnect();
    }

    /**
     * @group connected
     * @requires PHP 5.4
     */
    public function testPersistentConnectionsToSameNodeShareResource(): void
    {
        $connection1 = $this->createConnectionWithParams(array('persistent' => true));
        $connection2 = $this->createConnectionWithParams(array('persistent' => true));

        $this->assertPersistentConnection($connection1);
        $this->assertPersistentConnection($connection2);

        $this->assertSame($connection1->getResource(), $connection2->getResource());

        $connection1->disconnect();
    }

    /**
     * @group connected
     * @requires PHP 5.4
     */
    public function testPersistentConnectionsToSameNodeDoNotShareResourceUsingDifferentPersistentID(): void
    {
        $connection1 = $this->createConnectionWithParams(array('persistent' => 'conn1'));
        $connection2 = $this->createConnectionWithParams(array('persistent' => 'conn2'));

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
}
