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
     * @dataProvider errorFormatSettings
     */
    public function testThrowsStreamInitExceptionInsteadOfWarningOnFailedTlsHandshake(array $settings): void
    {
        $this->expectException(StreamInitException::class);
        $this->expectExceptionMessageMatches(
            '/^Error while switching to encrypted communication: SSL operation failed with code 1\. OpenSSL Error messages: error:[^\n<>&]+ \[tls:\/\/127\.0\.0\.1:6379\]$/'
        );

        $previous = [];
        foreach ($settings as $name => $value) {
            $previous[$name] = ini_set($name, $value);
        }

        try {
            $this->createTlsStreamToPlainTextPeer(['scheme' => 'tls']);
        } finally {
            foreach ($previous as $name => $value) {
                ini_set($name, $value);
            }
        }
    }

    public function errorFormatSettings(): array
    {
        return [
            'plain text' => [[]],
            'HTML' => [['html_errors' => '1']],
            'HTML with docs links' => [['html_errors' => '1', 'docref_root' => 'https://www.php.net/']],
        ];
    }

    /**
     * @group disconnected
     * @requires extension openssl
     * @requires PHP >= 8.1
     */
    public function testKeepsNonWarningErrorsDuringTlsHandshakeFromErrorHandlers(): void
    {
        $this->expectException(StreamInitException::class);

        // A float with a fraction raises E_DEPRECATED when coerced to the crypto method.
        $this->createTlsStreamToPlainTextPeer([
            'scheme' => 'tls',
            'ssl' => ['crypto_type' => STREAM_CRYPTO_METHOD_TLS_CLIENT + 0.5],
        ]);
    }

    /**
     * @group disconnected
     */
    public function testThrowsStreamInitExceptionInsteadOfWarningOnRefusedConnection(): void
    {
        $server = stream_socket_server('tcp://127.0.0.1:0');
        $port = $this->getPort($server);
        fclose($server);

        $this->expectException(StreamInitException::class);
        $this->expectExceptionMessageMatches("/^Connection refused \\[tcp:\\/\\/127\\.0\\.0\\.1:$port\\]$/");

        $this->callWithThrowingErrorHandler(static function () use ($port) {
            (new StreamFactory())->createStream(new Parameters(['port' => $port]));
        });
    }

    /**
     * @group disconnected
     */
    public function testSetsFractionalReadWriteTimeoutWithoutErrors(): void
    {
        $server = stream_socket_server('tcp://127.0.0.1:0');
        $port = $this->getPort($server);

        // On PHP 8.1+, 1.1 seconds would pass 100000.00000000009 microseconds.
        $stream = $this->callWithThrowingErrorHandler(static function () use ($port) {
            return (new StreamFactory())->createStream(new Parameters(['port' => $port, 'read_write_timeout' => 1.1]));
        });

        $this->assertInstanceOf(Stream::class, $stream);

        $stream->close();
        fclose($server);
    }

    /**
     * Creates a TLS stream to a peer that answers in plain text, failing the
     * handshake with a warning. The peer stays open until the handshake has
     * failed, so it fails on the reply rather than on EOF or a reset.
     */
    private function createTlsStreamToPlainTextPeer(array $parameters)
    {
        $server = stream_socket_server('tcp://127.0.0.1:0');
        $client = stream_socket_client(stream_socket_get_name($server, false));
        $peer = stream_socket_accept($server);
        fwrite($peer, "-ERR not a TLS server\r\n");

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

        try {
            return $this->callWithThrowingErrorHandler(static function () use ($factory, $parameters) {
                return $factory->createStream(new Parameters($parameters));
            });
        } finally {
            fclose($peer);
            fclose($server);

            $this->assertFalse(is_resource($client));
        }
    }

    /**
     * Calls the function with an error handler that throws on every error,
     * even ones silenced with @, and asserts that none reached PHP's own
     * handler either.
     */
    private function callWithThrowingErrorHandler(callable $callback)
    {
        set_error_handler(static function ($level, $message, $file = '', $line = 0) {
            throw new ErrorException($message, 0, $level, $file, $line);
        });
        $errorReporting = error_reporting(E_ALL);
        error_clear_last();

        try {
            return $callback();
        } finally {
            // Restored before PHPUnit loads classes that may emit deprecations.
            error_reporting($errorReporting);
            restore_error_handler();

            $this->assertNull(error_get_last());
        }
    }

    private function getPort($server): int
    {
        return (int) substr(strrchr(stream_socket_get_name($server, false), ':'), 1);
    }
}
