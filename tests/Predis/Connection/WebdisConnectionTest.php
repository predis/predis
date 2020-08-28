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

use PredisTestCase;

/**
 * @group ext-curl
 * @group ext-phpiredis
 * @group realm-connection
 * @group realm-webdis
 * @requires extension phpiredis
 * @requires extension curl
 */
class WebdisConnectionTest extends PredisTestCase
{
    /**
     * @group disconnected
     */
    public function testIsConnectedAlwaysReturnsTrue(): void
    {
        $connection = $this->createConnection();

        $this->assertTrue($connection->isConnected());
    }

    /**
     * @group disconnected
     */
    public function testSupportsSchemeUnix(): void
    {
        $connection = $this->createConnectionWithParams(array('scheme' => 'http'));

        $this->assertInstanceOf('Predis\Connection\NodeConnectionInterface', $connection);
    }

    /**
     * @group disconnected
     */
    public function testThrowsExceptionOnInvalidScheme(): void
    {
        $this->expectException('InvalidArgumentException');
        $this->expectExceptionMessage("Invalid scheme: 'tcp'");

        $connection = $this->createConnectionWithParams(array('scheme' => 'tcp'));
    }

    /**
     * @group disconnected
     */
    public function testWritingCommandsIsNotSupported(): void
    {
        $this->expectException('Predis\NotSupportedException');
        $this->expectExceptionMessage("The method Predis\Connection\WebdisConnection::writeRequest() is not supported");

        $connection = $this->createConnection();
        $connection->writeRequest($this->getCommandFactory()->create('ping'));
    }

    /**
     * @group disconnected
     */
    public function testReadingResponsesIsNotSupported(): void
    {
        $this->expectException('Predis\NotSupportedException');
        $this->expectExceptionMessage("The method Predis\Connection\WebdisConnection::readResponse() is not supported");

        $connection = $this->createConnection();
        $connection->readResponse($this->getCommandFactory()->create('ping'));
    }

    /**
     * @group disconnected
     */
    public function testReadingFromConnectionIsNotSupported(): void
    {
        $this->expectException('Predis\NotSupportedException');
        $this->expectExceptionMessage("The method Predis\Connection\WebdisConnection::read() is not supported");

        $connection = $this->createConnection();
        $connection->read();
    }

    /**
     * @group disconnected
     */
    public function testAddingConnectCommandsIsNotSupported(): void
    {
        $this->expectException('Predis\NotSupportedException');
        $this->expectExceptionMessage("The method Predis\Connection\WebdisConnection::addConnectCommand() is not supported");

        $connection = $this->createConnection();
        $connection->addConnectCommand($this->getCommandFactory()->create('ping'));
    }

    /**
     * @group disconnected
     */
    public function testRejectCommandSelect(): void
    {
        $this->expectException('Predis\NotSupportedException');
        $this->expectExceptionMessage("Command 'SELECT' is not allowed by Webdis");

        $connection = $this->createConnection();
        $connection->executeCommand($this->getCommandFactory()->create('select', array(0)));
    }

    /**
     * @group disconnected
     */
    public function testRejectCommandAuth(): void
    {
        $this->expectException('Predis\NotSupportedException');
        $this->expectExceptionMessage("Command 'AUTH' is not allowed by Webdis");

        $connection = $this->createConnection();
        $connection->executeCommand($this->getCommandFactory()->create('auth', array('foobar')));
    }

    /**
     * @group disconnected
     */
    public function testCanBeSerialized(): void
    {
        $parameters = $this->getParameters(array(
            'alias' => 'redis',
            'read_write_timeout' => 10,
        ));

        $connection = $this->createConnectionWithParams($parameters);

        $unserialized = unserialize(serialize($connection));

        $this->assertInstanceOf('Predis\Connection\WebdisConnection', $unserialized);
        $this->assertEquals($parameters, $unserialized->getParameters());
    }

    // ******************************************************************** //
    // ---- INTEGRATION TESTS --------------------------------------------- //
    // ******************************************************************** //

    /**
     * @group connected
     */
    public function testExecutesMultipleCommandsOnServer(): void
    {
        $commands = $this->getCommandFactory();

        $cmdPing = $commands->create('ping');
        $cmdEcho = $commands->create('echo', array('echoed'));
        $cmdGet = $commands->create('get', array('foobar'));
        $cmdRpush = $commands->create('rpush', array('metavars', 'foo', 'hoge', 'lol'));
        $cmdLrange = $commands->create('lrange', array('metavars', 0, -1));

        $connection = $this->createConnection(true);

        $this->assertEquals('PONG', $connection->executeCommand($cmdPing));
        $this->assertSame('echoed', $connection->executeCommand($cmdEcho));
        $this->assertNull($connection->executeCommand($cmdGet));
        $this->assertSame(3, $connection->executeCommand($cmdRpush));
        $this->assertSame(array('foo', 'hoge', 'lol'), $connection->executeCommand($cmdLrange));
    }

    /**
     * @medium
     * @group disconnected
     * @group slow
     */
    public function testThrowExceptionWhenUnableToConnect(): void
    {
        $this->expectException('Predis\Connection\ConnectionException');

        $connection = $this->createConnectionWithParams(array('host' => '169.254.10.10'));
        $connection->executeCommand($this->getCommandFactory()->create('ping'));
    }

    // ******************************************************************** //
    // ---- HELPER METHODS ------------------------------------------------ //
    // ******************************************************************** //

    /**
     * Returns a named array with the default connection parameters and their values.
     *
     * @return array Default connection parameters
     */
    protected function getDefaultParametersArray(): array
    {
        return array(
            'scheme' => 'http',
            'host' => constant('WEBDIS_SERVER_HOST'),
            'port' => constant('WEBDIS_SERVER_PORT'),
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function createConnection(): NodeConnectionInterface
    {
        return $this->createConnectionWithParams(array());
    }

    /**
     * {@inheritdoc}
     */
    protected function createConnectionWithParams($parameters): NodeConnectionInterface
    {
        if (!$parameters instanceof ParametersInterface) {
            $parameters = $this->getParameters($parameters);
        }

        $connection = new WebdisConnection($parameters);
        $connection->executeCommand($this->getCommandFactory()->create('flushdb'));

        return $connection;
    }
}
