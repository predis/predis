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
    public function testIsConnectedAlwaysReturnsTrue()
    {
        $connection = $this->createConnection();

        $this->assertTrue($connection->isConnected());
    }

    /**
     * @group disconnected
     */
    public function testSupportsSchemeUnix()
    {
        $connection = $this->createConnectionWithParams(array('scheme' => 'http'));

        $this->assertInstanceOf('Predis\Connection\NodeConnectionInterface', $connection);
    }

    /**
     * @group disconnected
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Invalid scheme: 'tcp'.
     */
    public function testThrowsExceptionOnInvalidScheme()
    {
        $connection = $this->createConnectionWithParams(array('scheme' => 'tcp'));
    }

    /**
     * @group disconnected
     * @expectedException \Predis\NotSupportedException
     * @expectedExceptionMessage The method Predis\Connection\WebdisConnection::writeRequest() is not supported.
     */
    public function testWritingCommandsIsNotSupported()
    {
        $connection = $this->createConnection();
        $connection->writeRequest($this->getCurrentProfile()->createCommand('ping'));
    }

    /**
     * @group disconnected
     * @expectedException \Predis\NotSupportedException
     * @expectedExceptionMessage The method Predis\Connection\WebdisConnection::readResponse() is not supported
     */
    public function testReadingResponsesIsNotSupported()
    {
        $connection = $this->createConnection();
        $connection->readResponse($this->getCurrentProfile()->createCommand('ping'));
    }

    /**
     * @group disconnected
     * @expectedException \Predis\NotSupportedException
     * @expectedExceptionMessage The method Predis\Connection\WebdisConnection::read() is not supported.
     */
    public function testReadingFromConnectionIsNotSupported()
    {
        $connection = $this->createConnection();
        $connection->read();
    }

    /**
     * @group disconnected
     * @expectedException \Predis\NotSupportedException
     * @expectedExceptionMessage The method Predis\Connection\WebdisConnection::addConnectCommand() is not supported.
     */
    public function testAddingConnectCommandsIsNotSupported()
    {
        $connection = $this->createConnection();
        $connection->addConnectCommand($this->getCurrentProfile()->createCommand('ping'));
    }

    /**
     * @group disconnected
     * @expectedException \Predis\NotSupportedException
     * @expectedExceptionMessage Command 'SELECT' is not allowed by Webdis.
     */
    public function testRejectCommandSelect()
    {
        $connection = $this->createConnection();
        $connection->executeCommand($this->getCurrentProfile()->createCommand('select', array(0)));
    }

    /**
     * @group disconnected
     * @expectedException \Predis\NotSupportedException
     * @expectedExceptionMessage Command 'AUTH' is not allowed by Webdis.
     */
    public function testRejectCommandAuth()
    {
        $connection = $this->createConnection();
        $connection->executeCommand($this->getCurrentProfile()->createCommand('auth', array('foobar')));
    }

    /**
     * @group disconnected
     */
    public function testCanBeSerialized()
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
    public function testExecutesMultipleCommandsOnServer()
    {
        $profile = $this->getCurrentProfile();

        $cmdPing = $profile->createCommand('ping');
        $cmdEcho = $profile->createCommand('echo', array('echoed'));
        $cmdGet = $profile->createCommand('get', array('foobar'));
        $cmdRpush = $profile->createCommand('rpush', array('metavars', 'foo', 'hoge', 'lol'));
        $cmdLrange = $profile->createCommand('lrange', array('metavars', 0, -1));

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
     * @expectedException \Predis\Connection\ConnectionException
     */
    public function testThrowExceptionWhenUnableToConnect()
    {
        $connection = $this->createConnectionWithParams(array('host' => '169.254.10.10'));
        $connection->executeCommand($this->getCurrentProfile()->createCommand('ping'));
    }

    // ******************************************************************** //
    // ---- HELPER METHODS ------------------------------------------------ //
    // ******************************************************************** //

    /**
     * Returns a named array with the default connection parameters and their values.
     *
     * @return array Default connection parameters.
     */
    protected function getDefaultParametersArray()
    {
        return array(
            'scheme' => 'http',
            'host' => WEBDIS_SERVER_HOST,
            'port' => WEBDIS_SERVER_PORT,
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function createConnection()
    {
        return $this->createConnectionWithParams(array());
    }

    /**
     * {@inheritdoc}
     */
    protected function createConnectionWithParams($parameters)
    {
        $profile = $this->getCurrentProfile();

        if (!$parameters instanceof ParametersInterface) {
            $parameters = $this->getParameters($parameters);
        }

        $connection = new WebdisConnection($parameters);
        $connection->executeCommand($profile->createCommand('flushdb'));

        return $connection;
    }
}
