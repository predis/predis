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

use Predis\Command\RawCommand;
use Predis\Response\Error as ErrorResponse;

/**
 * @group ext-phpiredis
 * @requires extension phpiredis
 */
class PhpiredisStreamConnectionTest extends PredisConnectionTestCase
{
    const CONNECTION_CLASS = 'Predis\Connection\PhpiredisStreamConnection';

    /**
     * @group disconnected
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage SSL encryption is not supported by this connection backend.
     */
    public function testSupportsSchemeTls()
    {
        $connection = $this->createConnectionWithParams(array('scheme' => 'tls'));

        $this->assertInstanceOf('Predis\Connection\NodeConnectionInterface', $connection);
    }

    /**
     * @group disconnected
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage SSL encryption is not supported by this connection backend.
     */
    public function testSupportsSchemeRediss()
    {
        $connection = $this->createConnectionWithParams(array('scheme' => 'rediss'));

        $this->assertInstanceOf('Predis\Connection\NodeConnectionInterface', $connection);
    }

    /**
     * @group disconnected
     * @expectedException \Predis\Connection\ConnectionException
     * @expectedExceptionMessage `SELECT` failed: ERR invalid DB index [tcp://127.0.0.1:6379]
     */
    public function testThrowsExceptionOnInitializationCommandFailure()
    {
        $cmdSelect = RawCommand::create('SELECT', '1000');

        $connection = $this->getMockBuilder(static::CONNECTION_CLASS)
                           ->setMethods(array('executeCommand', 'createResource'))
                           ->setConstructorArgs(array(new Parameters()))
                           ->getMock();

        $connection->method('executeCommand')
                   ->with($cmdSelect)
                   ->will($this->returnValue(
                       new ErrorResponse('ERR invalid DB index')
                   ));

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
        stream_socket_recvfrom($stream, 1);

        $connection->read();
    }

    /**
     * @group connected
     * @requires PHP 5.4
     */
    public function testPersistentParameterWithFalseLikeValues()
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
    public function testPersistentParameterWithTrueLikeValues()
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
    public function testPersistentConnectionsToSameNodeShareResource()
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
    public function testPersistentConnectionsToSameNodeDoNotShareResourceUsingDifferentPersistentID()
    {
        $connection1 = $this->createConnectionWithParams(array('persistent' => 'conn1'));
        $connection2 = $this->createConnectionWithParams(array('persistent' => 'conn2'));

        $this->assertPersistentConnection($connection1);
        $this->assertPersistentConnection($connection2);

        $this->assertNotSame($connection1->getResource(), $connection2->getResource());
    }
}
