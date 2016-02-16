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
 * @group realm-connection
 */
abstract class PredisConnectionTestCase extends PredisTestCase
{
    /**
     * @group disconnected
     */
    public function testConstructorDoesNotOpenConnection()
    {
        $connection = $this->createConnection();

        $this->assertFalse($connection->isConnected());
    }

    /**
     * @group disconnected
     */
    public function testSupportsSchemeTCP()
    {
        $connection = $this->createConnectionWithParams(array('scheme' => 'tcp'));

        $this->assertInstanceOf('Predis\Connection\NodeConnectionInterface', $connection);
    }

    /**
     * @group disconnected
     */
    public function testSupportsSchemeRedis()
    {
        $connection = $this->createConnectionWithParams(array('scheme' => 'redis'));

        $this->assertInstanceOf('Predis\Connection\NodeConnectionInterface', $connection);
    }

    /**
     * @group disconnected
     */
    public function testSupportsSchemeUnix()
    {
        $connection = $this->createConnectionWithParams(array('scheme' => 'unix'));

        $this->assertInstanceOf('Predis\Connection\NodeConnectionInterface', $connection);
    }

    /**
     * @group disconnected
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Invalid scheme: 'udp'.
     */
    public function testThrowsExceptionOnInvalidScheme()
    {
        $this->createConnectionWithParams(array('scheme' => 'udp'));
    }

    /**
     * @group disconnected
     */
    public function testExposesParameters()
    {
        $parameters = $this->getParameters();
        $connection = $this->createConnectionWithParams($parameters);

        $this->assertSame($parameters, $connection->getParameters());
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

        $this->assertInstanceOf(static::CONNECTION_CLASS, $unserialized);
        $this->assertEquals($parameters, $unserialized->getParameters());
    }

    /**
     * @group disconnected
     * @group slow
     * @expectedException \Predis\Connection\ConnectionException
     */
    public function testThrowExceptionWhenUnableToConnect()
    {
        $parameters = array('host' => '169.254.10.10', 'timeout' => 0.5);
        $connection = $this->createConnectionWithParams($parameters, false);
        $connection->executeCommand($this->getCurrentProfile()->createCommand('ping'));
    }

    // ******************************************************************** //
    // ---- INTEGRATION TESTS --------------------------------------------- //
    // ******************************************************************** //

    /**
     * @group connected
     * @requires PHP 5.4
     */
    public function testAcceptsTcpNodelayParameter()
    {
        $connection = $this->createConnectionWithParams(array('tcp_nodelay' => false));
        $connection->connect();
        $this->assertTrue($connection->isConnected());

        $connection = $this->createConnectionWithParams(array('tcp_nodelay' => true));
        $connection->connect();
        $this->assertTrue($connection->isConnected());
    }

    /**
     * @group connected
     */
    public function testConnectForcesConnection()
    {
        $connection = $this->createConnection();

        $this->assertFalse($connection->isConnected());
        $connection->connect();
        $this->assertTrue($connection->isConnected());
    }

    /**
     * @group connected
     */
    public function testDoesNotThrowExceptionOnConnectWhenAlreadyConnected()
    {
        $connection = $this->createConnection();

        $connection->connect();
        $this->assertTrue($connection->isConnected());

        $connection->connect();
        $this->assertTrue($connection->isConnected());
    }

    /**
     * @group connected
     */
    public function testDisconnectForcesDisconnection()
    {
        $connection = $this->createConnection();

        $connection->connect();
        $this->assertTrue($connection->isConnected());

        $connection->disconnect();
        $this->assertFalse($connection->isConnected());
    }

    /**
     * @group disconnected
     */
    public function testDoesNotThrowExceptionOnDisconnectWhenAlreadyDisconnected()
    {
        $connection = $this->createConnection();

        $this->assertFalse($connection->isConnected());
        $connection->disconnect();
        $this->assertFalse($connection->isConnected());
    }

    /**
     * @group connected
     */
    public function testGetResourceForcesConnection()
    {
        $connection = $this->createConnection();

        $this->assertFalse($connection->isConnected());
        $this->assertInternalType('resource', $connection->getResource());
        $this->assertTrue($connection->isConnected());
    }

    /**
     * @group connected
     */
    public function testSendingCommandForcesConnection()
    {
        $connection = $this->createConnection();
        $profile = $this->getCurrentProfile();

        $cmdPing = $profile->createCommand('ping');

        $this->assertEquals('PONG', $connection->executeCommand($cmdPing));
        $this->assertTrue($connection->isConnected());
    }

    /**
     * @group connected
     */
    public function testExecutesCommandOnServer()
    {
        $profile = $this->getCurrentProfile();

        $cmdPing = $this->getMock($profile->getCommandClass('ping'), array('parseResponse'));
        $cmdPing->expects($this->never())
                ->method('parseResponse');

        $connection = $this->createConnection();

        $this->assertEquals('PONG', $connection->executeCommand($cmdPing));
    }

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
     * @group connected
     */
    public function testWritesCommandToServer()
    {
        $profile = $this->getCurrentProfile();

        $cmdEcho = $this->getMock($profile->getCommandClass('echo'), array('parseResponse'));
        $cmdEcho->setArguments(array('ECHOED'));
        $cmdEcho->expects($this->never())
                ->method('parseResponse');

        $connection = $this->createConnection();
        $connection->writeRequest($cmdEcho);
        $connection->disconnect();
    }

    /**
     * @group connected
     */
    public function testReadsCommandFromServer()
    {
        $profile = $this->getCurrentProfile();

        $cmdEcho = $this->getMock($profile->getCommandClass('echo'), array('parseResponse'));
        $cmdEcho->setArguments(array('ECHOED'));
        $cmdEcho->expects($this->never())
                ->method('parseResponse');

        $connection = $this->createConnection();
        $connection->writeRequest($cmdEcho);

        $this->assertSame('ECHOED', $connection->readResponse($cmdEcho));
    }

    /**
     * @group connected
     */
    public function testIsAbleToWriteMultipleCommandsAndReadThemBackForPipelining()
    {
        $profile = $this->getCurrentProfile();

        $cmdPing = $this->getMock($profile->getCommandClass('ping'), array('parseResponse'));
        $cmdPing->expects($this->never())
                ->method('parseResponse');

        $cmdEcho = $this->getMock($profile->getCommandClass('echo'), array('parseResponse'));
        $cmdEcho->setArguments(array('ECHOED'));
        $cmdEcho->expects($this->never())
                ->method('parseResponse');

        $connection = $this->createConnection();

        $connection->writeRequest($cmdPing);
        $connection->writeRequest($cmdEcho);

        $this->assertEquals('PONG', $connection->readResponse($cmdPing));
        $this->assertSame('ECHOED', $connection->readResponse($cmdEcho));
    }

    /**
     * @group connected
     */
    public function testSendsInitializationCommandsOnConnection()
    {
        $profile = $this->getCurrentProfile();

        $cmdPing = $this->getMock($profile->getCommandClass('ping'), array('getArguments'));
        $cmdPing->expects($this->once())
                ->method('getArguments')
                ->will($this->returnValue(array()));

        $cmdEcho = $this->getMock($profile->getCommandClass('echo'), array('getArguments'));
        $cmdEcho->expects($this->once())
                ->method('getArguments')
                ->will($this->returnValue(array('ECHOED')));

        $connection = $this->createConnection();
        $connection->addConnectCommand($cmdPing);
        $connection->addConnectCommand($cmdEcho);

        $connection->connect();
    }

    /**
     * @group connected
     */
    public function testReadsStatusResponses()
    {
        $profile = $this->getCurrentProfile();
        $connection = $this->createConnection(true);

        $connection->writeRequest($profile->createCommand('set', array('foo', 'bar')));
        $this->assertInstanceOf('Predis\Response\Status', $connection->read());

        $connection->writeRequest($profile->createCommand('ping'));
        $this->assertInstanceOf('Predis\Response\Status', $connection->read());

        $connection->writeRequest($profile->createCommand('multi'));
        $connection->writeRequest($profile->createCommand('ping'));
        $this->assertInstanceOf('Predis\Response\Status', $connection->read());
        $this->assertInstanceOf('Predis\Response\Status', $connection->read());
    }

    /**
     * @group connected
     */
    public function testReadsBulkResponses()
    {
        $profile = $this->getCurrentProfile();
        $connection = $this->createConnection(true);

        $connection->executeCommand($profile->createCommand('set', array('foo', 'bar')));

        $connection->writeRequest($profile->createCommand('get', array('foo')));
        $this->assertSame('bar', $connection->read());

        $connection->writeRequest($profile->createCommand('get', array('hoge')));
        $this->assertNull($connection->read());
    }

    /**
     * @group connected
     */
    public function testReadsIntegerResponses()
    {
        $profile = $this->getCurrentProfile();
        $connection = $this->createConnection(true);

        $connection->executeCommand($profile->createCommand('rpush', array('metavars', 'foo', 'hoge', 'lol')));
        $connection->writeRequest($profile->createCommand('llen', array('metavars')));

        $this->assertSame(3, $connection->read());
    }

    /**
     * @group connected
     */
    public function testReadsErrorResponsesAsResponseErrorObjects()
    {
        $profile = $this->getCurrentProfile();
        $connection = $this->createConnection(true);

        $connection->executeCommand($profile->createCommand('set', array('foo', 'bar')));
        $connection->writeRequest($profile->createCommand('rpush', array('foo', 'baz')));

        $this->assertInstanceOf('Predis\Response\Error', $error = $connection->read());
        $this->assertRegExp('/[ERR|WRONGTYPE] Operation against a key holding the wrong kind of value/', $error->getMessage());
    }

    /**
     * @group connected
     */
    public function testReadsMultibulkResponsesAsArrays()
    {
        $profile = $this->getCurrentProfile();
        $connection = $this->createConnection(true);

        $connection->executeCommand($profile->createCommand('rpush', array('metavars', 'foo', 'hoge', 'lol')));
        $connection->writeRequest($profile->createCommand('lrange', array('metavars', 0, -1)));

        $this->assertSame(array('foo', 'hoge', 'lol'), $connection->read());
    }

    /**
     * @group connected
     * @group slow
     * @expectedException \Predis\Connection\ConnectionException
     * @expectedExceptionMessageRegExp /.* \[tcp:\/\/169.254.10.10:6379\]/
     */
    public function testThrowsExceptionOnConnectionTimeout()
    {
        $connection = $this->createConnectionWithParams(array(
            'host' => '169.254.10.10',
            'timeout' => 0.1,
        ), false);

        $connection->connect();
    }

    /**
     * @group connected
     * @group slow
     * @expectedException \Predis\Connection\ConnectionException
     * @expectedExceptionMessageRegExp /.* \[tcp:\/\/\[0:0:0:0:0:ffff:a9fe:a0a\]:6379\]/
     */
    public function testThrowsExceptionOnConnectionTimeoutIPv6()
    {
        $connection = $this->createConnectionWithParams(array(
            'host' => '0:0:0:0:0:ffff:a9fe:a0a',
            'timeout' => 0.1,
        ), false);

        $connection->connect();
    }

    /**
     * @group connected
     * @group slow
     * @expectedException \Predis\Connection\ConnectionException
     * @expectedExceptionMessageRegExp /.* \[unix:\/tmp\/nonexistent\/redis\.sock]/
     */
    public function testThrowsExceptionOnUnixDomainSocketNotFound()
    {
        $connection = $this->createConnectionWithParams(array(
            'scheme' => 'unix',
            'path' => '/tmp/nonexistent/redis.sock',
        ), false);

        $connection->connect();
    }

    /**
     * @group connected
     * @group slow
     * @expectedException \Predis\Connection\ConnectionException
     */
    public function testThrowsExceptionOnReadWriteTimeout()
    {
        $profile = $this->getCurrentProfile();

        $connection = $this->createConnectionWithParams(array(
            'read_write_timeout' => 0.5,
        ), true);

        $connection->executeCommand($profile->createCommand('brpop', array('foo', 3)));
    }

    /**
     * @medium
     * @group connected
     * @expectedException \Predis\Protocol\ProtocolException
     */
    public function testThrowsExceptionOnProtocolDesynchronizationErrors()
    {
        $connection = $this->createConnection();
        $stream = $connection->getResource();

        $connection->writeRequest($this->getCurrentProfile()->createCommand('ping'));
        fread($stream, 1);

        $connection->read();
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
            'scheme' => 'tcp',
            'host' => REDIS_SERVER_HOST,
            'port' => REDIS_SERVER_PORT,
            'database' => REDIS_SERVER_DBNUM,
            'read_write_timeout' => 2,
        );
    }

    /**
     * Asserts that the connection is using a persistent resource stream.
     *
     * This assertion will trigger a connect() operation if the connection has
     * not been open yet.
     *
     * @param NodeConnectionInterface $connection Connection instance.
     */
    protected function assertPersistentConnection(NodeConnectionInterface $connection)
    {
        $this->assertSame('persistent stream', get_resource_type($connection->getResource()));
    }

    /**
     * Asserts that the connection is not using a persistent resource stream.
     *
     * This assertion will trigger a connect() operation if the connection has
     * not been open yet.
     *
     * @param NodeConnectionInterface $connection Connection instance.
     */
    protected function assertNonPersistentConnection(NodeConnectionInterface $connection)
    {
        $this->assertSame('stream', get_resource_type($connection->getResource()));
    }

    /**
     * Creates a new connection instance.
     *
     * @param bool $initialize Push default initialization commands (SELECT and FLUSHDB).
     *
     * @return NodeConnectionInterface
     */
    protected function createConnection($initialize = false)
    {
        return $this->createConnectionWithParams(array(), $initialize);
    }

    /**
     * Creates a new connection instance using additional connection parameters.
     *
     * @param mixed $parameters Additional connection parameters.
     * @param bool  $initialize Push default initialization commands (SELECT and FLUSHDB).
     *
     * @return NodeConnectionInterface
     */
    protected function createConnectionWithParams($parameters, $initialize = false)
    {
        $class = static::CONNECTION_CLASS;
        $profile = $this->getCurrentProfile();

        if (!$parameters instanceof ParametersInterface) {
            $parameters = $this->getParameters($parameters);
        }

        $connection = new $class($parameters);

        if ($initialize) {
            $connection->addConnectCommand(
                $profile->createCommand('select', array($parameters->database))
            );

            $connection->addConnectCommand(
                $profile->createCommand('flushdb')
            );
        }

        return $connection;
    }
}
