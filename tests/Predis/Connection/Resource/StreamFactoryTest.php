<?php

/*
 * This file is part of the Predis package.
 *
 * (c) 2009-2020 Daniele Alessandri
 * (c) 2021-2026 Till Krüss
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Connection\Resource;

use ErrorException;
use PHPUnit\Framework\TestCase;
use Predis\Connection\Parameters;
use Predis\Connection\ParametersInterface;
use Predis\Connection\Resource\Exception\StreamInitException;

class StreamFactoryTest extends TestCase
{
    /**
     * @group disconnected
     * @requires extension openssl
     */
    public function testThrowsStreamInitExceptionInsteadOfWarningOnFailedTlsHandshake(): void
    {
        $server = stream_socket_server('tcp://127.0.0.1:0');
        $client = stream_socket_client(stream_socket_get_name($server, false));
        $peer = stream_socket_accept($server);

        // A non-TLS reply makes the handshake fail with a PHP warning.
        fwrite($peer, "-ERR not a TLS server\r\n");
        fclose($peer);

        $factory = new class($client) extends StreamFactory {
            private $client;

            public function __construct($client)
            {
                $this->client = $client;
            }

            protected function tcpStreamInitializer(ParametersInterface $parameters)
            {
                return $this->client;
            }
        };

        $this->expectException(StreamInitException::class);
        $this->expectExceptionMessage('Error while switching to encrypted communication [tls:');

        // Mimics Laravel: warnings become exceptions unless silenced with @.
        // Restored in finally, before PHPUnit loads classes that may emit deprecations.
        set_error_handler(static function ($level, $message, $file = '', $line = 0) {
            if (error_reporting() & $level) {
                throw new ErrorException($message, 0, $level, $file, $line);
            }

            return false;
        });
        $errorReporting = error_reporting(E_ALL);

        try {
            $factory->createStream(new Parameters(['scheme' => 'tls']));
        } finally {
            error_reporting($errorReporting);
            restore_error_handler();
            fclose($server);
        }
    }
}
