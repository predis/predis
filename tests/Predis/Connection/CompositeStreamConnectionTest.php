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
class CompositeStreamConnectionTest extends PredisConnectionTestCase
{
    /**
     * @group disconnected
     */
    public function testConstructorDoesNotOpenConnection()
    {
        $connection = new CompositeStreamConnection($this->getParameters());

        $this->assertFalse($connection->isConnected());
    }

    /**
     * @group disconnected
     */
    public function testExposesParameters()
    {
        $parameters = $this->getParameters();
        $connection = new CompositeStreamConnection($parameters);

        $this->assertSame($parameters, $connection->getParameters());
    }

    /**
     * @group disconnected
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Invalid scheme: 'udp'.
     */
    public function testThrowsExceptionOnInvalidScheme()
    {
        $parameters = $this->getParameters(array('scheme' => 'udp'));
        new CompositeStreamConnection($parameters);
    }

    /**
     * @group disconnected
     */
    public function testCanBeSerialized()
    {
        $parameters = $this->getParameters(array('alias' => 'redis', 'read_write_timeout' => 10));
        $connection = new CompositeStreamConnection($parameters);

        $unserialized = unserialize(serialize($connection));

        $this->assertEquals($connection, $unserialized);
    }

    // ******************************************************************** //
    // ---- INTEGRATION TESTS --------------------------------------------- //
    // ******************************************************************** //

    /**
     * @group connected
     */
    public function testReadsMultibulkResponsesAsIterators()
    {
        $connection = $this->getConnection($profile, true);
        $connection->getProtocol()->useIterableMultibulk(true);

        $connection->executeCommand($profile->createCommand('rpush', array('metavars', 'foo', 'hoge', 'lol')));
        $connection->writeRequest($profile->createCommand('lrange', array('metavars', 0, -1)));

        $this->assertInstanceOf('Predis\Response\Iterator\MultiBulkIterator', $iterator = $connection->read());
        $this->assertSame(array('foo', 'hoge', 'lol'), iterator_to_array($iterator));
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

        $connection = new CompositeStreamConnection($parameters);

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
