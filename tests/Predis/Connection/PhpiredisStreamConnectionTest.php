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
}
