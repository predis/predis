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
class PhpiredisSocketConnectionTest extends PredisConnectionTestCase
{
    const CONNECTION_CLASS = 'Predis\Connection\PhpiredisSocketConnection';

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
