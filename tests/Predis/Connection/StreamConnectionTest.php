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

/**
 *
 */
class StreamConnectionTest extends PredisConnectionTestCase
{
    const CONNECTION_CLASS = 'Predis\Connection\StreamConnection';

    /**
     * @group disconnected
     */
    public function testConstructorDoesNotOpenConnection()
    {
        $connection = new StreamConnection($this->getParameters());

        $this->assertFalse($connection->isConnected());
    }

    /**
     * @group disconnected
     */
    public function testSupportsSchemeTCP()
    {
        $parameters = $this->getParameters(array('scheme' => 'tcp'));
        $connection = new StreamConnection($parameters);

        $this->assertInstanceOf('Predis\Connection\NodeConnectionInterface', $connection);
    }

    /**
     * @group disconnected
     */
    public function testSupportsSchemeRedis()
    {
        $parameters = $this->getParameters(array('scheme' => 'redis'));
        $connection = new StreamConnection($parameters);

        $this->assertInstanceOf('Predis\Connection\NodeConnectionInterface', $connection);
    }

    /**
     * @group disconnected
     */
    public function testSupportsSchemeUnix()
    {
        $parameters = $this->getParameters(array('scheme' => 'unix'));
        $connection = new StreamConnection($parameters);

        $this->assertInstanceOf('Predis\Connection\NodeConnectionInterface', $connection);
    }

    /**
     * @group disconnected
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Invalid scheme: 'udp'.
     */
    public function testThrowsExceptionOnInvalidScheme()
    {
        $parameters = $this->getParameters(array('scheme' => 'udp'));
        new StreamConnection($parameters);
    }

    /**
     * @group disconnected
     */
    public function testExposesParameters()
    {
        $parameters = $this->getParameters();
        $connection = new StreamConnection($parameters);

        $this->assertSame($parameters, $connection->getParameters());
    }

    /**
     * @group disconnected
     */
    public function testCanBeSerialized()
    {
        $parameters = $this->getParameters(array('alias' => 'redis', 'read_write_timeout' => 10));
        $connection = new StreamConnection($parameters);

        $unserialized = unserialize(serialize($connection));

        $this->assertEquals($connection, $unserialized);
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
        $connection = new StreamConnection($this->getParameters(array('tcp_nodelay' => false)));
        $connection->connect();
        $this->assertTrue($connection->isConnected());

        $connection = new StreamConnection($this->getParameters(array('tcp_nodelay' => true)));
        $connection->connect();
        $this->assertTrue($connection->isConnected());
    }

    /**
     * @group connected
     * @expectedException \Predis\Protocol\ProtocolException
     * @expectedExceptionMessage Unknown response prefix: 'P'.
     */
    public function testThrowsExceptionOnProtocolDesynchronizationErrors()
    {
        $connection = $this->getConnection($profile);
        $stream = $connection->getResource();

        $connection->writeRequest($profile->createCommand('ping'));
        fread($stream, 1);

        $connection->read();
    }
}
