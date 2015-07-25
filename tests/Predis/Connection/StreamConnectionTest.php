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
     */
    public function testAcceptsTcpNodelayParameter()
    {
        if (!version_compare(PHP_VERSION, '5.4.0', '>=')) {
            $this->markTestSkipped('Setting TCP_NODELAY on PHP socket streams works on PHP >= 5.4.0');
        }

        $connection = new StreamConnection($this->getParameters(array('tcp_nodelay' => false)));
        $connection->connect();
        $this->assertTrue($connection->isConnected());

        $connection = new StreamConnection($this->getParameters(array('tcp_nodelay' => true)));
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

        $connection1 = new StreamConnection($this->getParameters(array('persistent' => 0)));
        $this->assertNonPersistentConnection($connection1);

        $connection2 = new StreamConnection($this->getParameters(array('persistent' => false)));
        $this->assertNonPersistentConnection($connection2);

        $connection3 = new StreamConnection($this->getParameters(array('persistent' => '0')));
        $this->assertNonPersistentConnection($connection3);

        $connection4 = new StreamConnection($this->getParameters(array('persistent' => 'false')));
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

        $connection1 = new StreamConnection($this->getParameters(array('persistent' => 1)));
        $this->assertPersistentConnection($connection1);

        $connection2 = new StreamConnection($this->getParameters(array('persistent' => true)));
        $this->assertPersistentConnection($connection2);

        $connection3 = new StreamConnection($this->getParameters(array('persistent' => '1')));
        $this->assertPersistentConnection($connection3);

        $connection4 = new StreamConnection($this->getParameters(array('persistent' => 'true')));
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

        $connection1 = new StreamConnection($this->getParameters(array('persistent' => true)));
        $connection2 = new StreamConnection($this->getParameters(array('persistent' => true)));

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

        $connection1 = new StreamConnection($this->getParameters(array('persistent' => 'conn1')));
        $connection2 = new StreamConnection($this->getParameters(array('persistent' => 'conn2')));

        $this->assertPersistentConnection($connection1);
        $this->assertPersistentConnection($connection2);

        $this->assertNotSame($connection1->getResource(), $connection2->getResource());
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

    // ******************************************************************** //
    // ---- HELPER METHODS ------------------------------------------------ //
    // ******************************************************************** //

    /**
     * {@inheritdoc}
     */
    protected function getConnection(&$profile = null, $initialize = false, array $parameters = array())
    {
        $parameters = $this->getParameters($parameters);
        $profile = $this->getProfile();

        $connection = new StreamConnection($parameters);

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
