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
 * @group ext-phpiredis
 * @requires extension phpiredis
 */
class PhpiredisStreamConnectionTest extends PredisConnectionTestCase
{
    const CONNECTION_CLASS = 'Predis\Connection\PhpiredisStreamConnection';

    /**
     * @group disconnected
     */
    public function testConstructorDoesNotOpenConnection()
    {
        $connection = new PhpiredisStreamConnection($this->getParameters());

        $this->assertFalse($connection->isConnected());
    }

    /**
     * @group disconnected
     */
    public function testSupportsSchemeTCP()
    {
        $parameters = $this->getParameters(array('scheme' => 'tcp'));
        $connection = new PhpiredisStreamConnection($parameters);

        $this->assertInstanceOf('Predis\Connection\NodeConnectionInterface', $connection);
    }

    /**
     * @group disconnected
     */
    public function testSupportsSchemeRedis()
    {
        $parameters = $this->getParameters(array('scheme' => 'redis'));
        $connection = new PhpiredisStreamConnection($parameters);

        $this->assertInstanceOf('Predis\Connection\NodeConnectionInterface', $connection);
    }

    /**
     * @group disconnected
     */
    public function testSupportsSchemeUnix()
    {
        $parameters = $this->getParameters(array('scheme' => 'unix'));
        $connection = new PhpiredisStreamConnection($parameters);

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
        new PhpiredisStreamConnection($parameters);
    }

    /**
     * @group disconnected
     */
    public function testExposesParameters()
    {
        $parameters = $this->getParameters();
        $connection = new PhpiredisStreamConnection($parameters);

        $this->assertSame($parameters, $connection->getParameters());
    }

    /**
     * @group disconnected
     */
    public function testCanBeSerialized()
    {
        $parameters = $this->getParameters(array('alias' => 'redis', 'read_write_timeout' => 10));
        $connection = new PhpiredisStreamConnection($parameters);

        $unserialized = unserialize(serialize($connection));

        $this->assertInstanceOf('Predis\Connection\PhpiredisStreamConnection', $unserialized);
        $this->assertEquals($parameters, $unserialized->getParameters());
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
        $connection = new PhpiredisStreamConnection($this->getParameters(array('tcp_nodelay' => false)));
        $connection->connect();
        $this->assertTrue($connection->isConnected());

        $connection = new PhpiredisStreamConnection($this->getParameters(array('tcp_nodelay' => true)));
        $connection->connect();
        $this->assertTrue($connection->isConnected());
    }

    /**
     * @group connected
     */
    public function testPersistentParameterWithFalseLikeValues()
    {
        if ($this->isHHVM()) {
            $this->markTestSkipped('This test does not currently work on HHVM.');
        }

        $connection1 = new PhpiredisStreamConnection($this->getParameters(array('persistent' => 0)));
        $this->assertNonPersistentConnection($connection1);

        $connection2 = new PhpiredisStreamConnection($this->getParameters(array('persistent' => false)));
        $this->assertNonPersistentConnection($connection2);

        $connection3 = new PhpiredisStreamConnection($this->getParameters(array('persistent' => '0')));
        $this->assertNonPersistentConnection($connection3);

        $connection4 = new PhpiredisStreamConnection($this->getParameters(array('persistent' => 'false')));
        $this->assertNonPersistentConnection($connection4);
    }

    /**
     * @group connected
     */
    public function testPersistentParameterWithTrueLikeValues()
    {
        if ($this->isHHVM()) {
            $this->markTestSkipped('This test does not currently work on HHVM.');
        }

        $connection1 = new PhpiredisStreamConnection($this->getParameters(array('persistent' => 1)));
        $this->assertPersistentConnection($connection1);

        $connection2 = new PhpiredisStreamConnection($this->getParameters(array('persistent' => true)));
        $this->assertPersistentConnection($connection2);

        $connection3 = new PhpiredisStreamConnection($this->getParameters(array('persistent' => '1')));
        $this->assertPersistentConnection($connection3);

        $connection4 = new PhpiredisStreamConnection($this->getParameters(array('persistent' => 'true')));
        $this->assertPersistentConnection($connection4);

        $connection1->disconnect();
    }

    /**
     * @group connected
     */
    public function testPersistentConnectionsToSameNodeShareResource()
    {
        if ($this->isHHVM()) {
            $this->markTestSkipped('This test does not currently work on HHVM.');
        }

        $connection1 = new PhpiredisStreamConnection($this->getParameters(array('persistent' => true)));
        $connection2 = new PhpiredisStreamConnection($this->getParameters(array('persistent' => true)));

        $this->assertPersistentConnection($connection1);
        $this->assertPersistentConnection($connection2);

        $this->assertSame($connection1->getResource(), $connection2->getResource());

        $connection1->disconnect();
    }

    /**
     * @group connected
     */
    public function testPersistentConnectionsToSameNodeDoNotShareResourceUsingDifferentPersistentID()
    {
        if ($this->isHHVM()) {
            $this->markTestSkipped('This test does not currently work on HHVM.');
        }

        $connection1 = new PhpiredisStreamConnection($this->getParameters(array('persistent' => 'conn1')));
        $connection2 = new PhpiredisStreamConnection($this->getParameters(array('persistent' => 'conn2')));

        $this->assertPersistentConnection($connection1);
        $this->assertPersistentConnection($connection2);

        $this->assertNotSame($connection1->getResource(), $connection2->getResource());
    }

    /**
     * @medium
     * @group connected
     * @expectedException \Predis\Protocol\ProtocolException
     * @expectedExceptionMessage Protocol error, got "P" as reply type byte
     */
    public function testThrowsExceptionOnProtocolDesynchronizationErrors()
    {
        $connection = $this->getConnection($profile);
        $socket = $connection->getResource();

        $connection->writeRequest($profile->createCommand('ping'));
        fread($socket, 1);

        $connection->read();
    }
}
