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
class PhpiredisSocketConnectionTest extends PredisConnectionTestCase
{
    const CONNECTION_CLASS = 'Predis\Connection\PhpiredisSocketConnection';

    /**
     * @group disconnected
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Invalid scheme: 'tls'.
     */
    public function testSupportsSchemeTls()
    {
        $connection = $this->createConnectionWithParams(array('scheme' => 'tls'));

        $this->assertInstanceOf('Predis\Connection\NodeConnectionInterface', $connection);
    }

    /**
     * @group disconnected
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Invalid scheme: 'rediss'.
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
     * @expectedException \Predis\Connection\ConnectionException
     * @expectedExceptionMessage Cannot resolve the address of 'bogus.tld'.
     */
    public function testThrowsExceptionOnUnresolvableHostname()
    {
        $connection = $this->createConnectionWithParams(array('host' => 'bogus.tld'));
        $connection->connect();
    }

    /**
     * @medium
     * @group connected
     * @expectedException \Predis\Protocol\ProtocolException
     */
    public function testThrowsExceptionOnProtocolDesynchronizationErrors()
    {
        $connection = $this->createConnection();
        $socket = $connection->getResource();

        $connection->writeRequest($this->getCurrentProfile()->createCommand('ping'));
        socket_read($socket, 1);

        $connection->read();
    }
}
