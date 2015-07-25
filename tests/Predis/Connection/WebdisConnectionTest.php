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
    const CONNECTION_CLASS = 'Predis\Connection\WebdisConnection';

    /**
     * @group disconnected
     */
    public function testIsConnectedAlwaysReturnsTrue()
    {
        $connection = new WebdisConnection($this->getParameters());

        $this->assertTrue($connection->isConnected());
    }

    /**
     * @group disconnected
     */
    public function testSupportsSchemeUnix()
    {
        $parameters = $this->getParameters(array('scheme' => 'http'));
        $connection = new WebdisConnection($parameters);

        $this->assertInstanceOf('Predis\Connection\NodeConnectionInterface', $connection);
    }

    /**
     * @group disconnected
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Invalid scheme: 'tcp'.
     */
    public function testThrowsExceptionOnInvalidScheme()
    {
        $parameters = $this->getParameters(array('scheme' => 'tcp'));
        new WebdisConnection($parameters);
    }

    /**
     * @group disconnected
     * @expectedException \Predis\NotSupportedException
     * @expectedExceptionMessage The method Predis\Connection\WebdisConnection::writeRequest() is not supported.
     */
    public function testWritingCommandsIsNotSupported()
    {
        $connection = new WebdisConnection($this->getParameters());
        $connection->writeRequest($this->getProfile()->createCommand('ping'));
    }

    /**
     * @group disconnected
     * @expectedException \Predis\NotSupportedException
     * @expectedExceptionMessage The method Predis\Connection\WebdisConnection::readResponse() is not supported
     */
    public function testReadingResponsesIsNotSupported()
    {
        $connection = new WebdisConnection($this->getParameters());
        $connection->readResponse($this->getProfile()->createCommand('ping'));
    }

    /**
     * @group disconnected
     * @expectedException \Predis\NotSupportedException
     * @expectedExceptionMessage The method Predis\Connection\WebdisConnection::read() is not supported.
     */
    public function testReadingFromConnectionIsNotSupported()
    {
        $connection = new WebdisConnection($this->getParameters());
        $connection->read();
    }

    /**
     * @group disconnected
     * @expectedException \Predis\NotSupportedException
     * @expectedExceptionMessage The method Predis\Connection\WebdisConnection::addConnectCommand() is not supported.
     */
    public function testAddingConnectCommandsIsNotSupported()
    {
        $connection = new WebdisConnection($this->getParameters());
        $connection->addConnectCommand($this->getProfile()->createCommand('ping'));
    }

    /**
     * @group disconnected
     * @expectedException \Predis\NotSupportedException
     * @expectedExceptionMessage Command 'SELECT' is not allowed by Webdis.
     */
    public function testRejectCommandSelect()
    {
        $connection = new WebdisConnection($this->getParameters());
        $connection->executeCommand($this->getProfile()->createCommand('select', array(0)));
    }

    /**
     * @group disconnected
     * @expectedException \Predis\NotSupportedException
     * @expectedExceptionMessage Command 'AUTH' is not allowed by Webdis.
     */
    public function testRejectCommandAuth()
    {
        $connection = new WebdisConnection($this->getParameters());
        $connection->executeCommand($this->getProfile()->createCommand('auth', array('foobar')));
    }

    /**
     * @group disconnected
     */
    public function testCanBeSerialized()
    {
        $parameters = $this->getParameters(array('alias' => 'webdis'));
        $connection = new WebdisConnection($parameters);

        $unserialized = unserialize(serialize($connection));

        $this->assertInstanceOf('Predis\Connection\WebdisConnection', $unserialized);
        $this->assertEquals($parameters, $unserialized->getParameters());
    }

    // ******************************************************************** //
    // ---- INTEGRATION TESTS --------------------------------------------- //
    // ******************************************************************** //

    /**
     * @medium
     * @group disconnected
     * @group slow
     * @expectedException \Predis\Connection\ConnectionException
     */
    public function testThrowExceptionWhenUnableToConnect()
    {
        $parameters = $this->getParameters(array('host' => '169.254.10.10'));
        $connection = new WebdisConnection($parameters);
        $connection->executeCommand($this->getProfile()->createCommand('ping'));
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
    protected function getConnection(&$profile = null, array $parameters = array())
    {
        $class = static::CONNECTION_CLASS;

        $parameters = $this->getParameters($parameters);
        $profile = $this->getProfile();

        $connection = new $class($parameters);
        $connection->executeCommand($profile->createCommand('flushdb'));

        return $connection;
    }
}
