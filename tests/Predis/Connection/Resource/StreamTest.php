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
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class StreamTest extends TestCase
{
    /**
     * @return void
     */
    public function testConstructThrowsExceptionOnInvalidResource(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Given stream is not a valid resource');

        new Stream(false);
    }

    /**
     * @return void
     */
    public function testToStringReturnsAllRemainingContent(): void
    {
        $handle = fopen('php://temp', 'rb+');
        fwrite($handle, 'data');
        $stream = new Stream($handle);

        $this->assertSame('data', (string) $stream);
    }

    /**
     * @return void
     */
    public function testClosesStream(): void
    {
        $handle = fopen('php://temp', 'rb+');
        fwrite($handle, 'data');
        $stream = new Stream($handle);

        $stream->close();
        $this->assertTrue(true);
    }

    /**
     * @return void
     */
    public function testDetachReturnsStreamAndDetachItFromObject(): void
    {
        $handle = fopen('php://temp', 'rb+');
        fwrite($handle, 'data');
        $stream = new Stream($handle);
        $detachedStream = $stream->detach();
        fseek($detachedStream, 0);

        $this->assertSame('data', stream_get_contents($detachedStream));
        $this->assertNull($stream->detach());
        $this->assertFalse($stream->isReadable());
        $this->assertFalse($stream->isWritable());
        $this->assertFalse($stream->isSeekable());
    }

    /**
     * @return void
     */
    public function testGetSize(): void
    {
        $handle = fopen('php://temp', 'rb+');
        fwrite($handle, 'data');
        $stream = new Stream($handle);

        $this->assertSame(4, $stream->getSize());
        $stream->detach();
        $this->assertNull($stream->getSize());
    }

    /**
     * @return void
     */
    public function testTellThrowsExceptionOnDetachedStream(): void
    {
        $handle = fopen('php://temp', 'rb+');
        $stream = new Stream($handle);
        $stream->detach();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Stream is detached');

        $stream->tell();
    }

    /**
     * @return void
     */
    public function testTellReturnsCurrentPosition(): void
    {
        $handle = fopen('php://temp', 'rb+');
        $stream = new Stream($handle);

        $this->assertSame(0, $stream->tell());
    }

    /**
     * @return void
     */
    public function testEofChecksIfPointerAtTheEndOfTheStream(): void
    {
        $handle = fopen('php://temp', 'rb+');
        $stream = new Stream($handle);
        $stream->read(1);

        $this->assertTrue($stream->eof());
    }

    /**
     * @return void
     */
    public function testEofThrowsExceptionOnDetachedStream(): void
    {
        $handle = fopen('php://temp', 'rb+');
        $stream = new Stream($handle);
        $stream->detach();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Stream is detached');

        $stream->eof();
    }

    /**
     * @return void
     */
    public function testIsSeekable(): void
    {
        $handle = fopen('php://temp', 'rb+');
        $stream = new Stream($handle);

        $this->assertTrue($stream->isSeekable());
    }

    /**
     * @return void
     */
    public function testSeekThrowsExceptionOnDetachedStream(): void
    {
        $handle = fopen('php://temp', 'rb+');
        $stream = new Stream($handle);
        $stream->detach();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Stream is detached');

        $stream->seek(0, 1);
    }

    /**
     * @return void
     * @requires PHP < 8.3
     */
    public function testSeekThrowsExceptionOnIncorrectOffset(): void
    {
        $handle = fopen('php://temp', 'rb+');
        $stream = new Stream($handle);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Unable to seek stream from offset 10 to whence 1');

        $stream->seek(10, 1);
    }

    /**
     * @return void
     * @requires PHP > 8.3
     */
    public function testSeekThrowsExceptionOnIncorrectOffsetAndWhence(): void
    {
        $handle = fopen('php://temp', 'rb+');
        $stream = new Stream($handle);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Unable to seek stream from offset 10 to whence 100');

        $stream->seek(10, 100);
    }

    /**
     * @return void
     */
    public function testRewind(): void
    {
        $handle = fopen('php://temp', 'rb+');
        $stream = new Stream($handle);

        $stream->rewind();
        $this->assertTrue(true);
    }

    /**
     * @dataProvider writableModeProvider
     * @param  string $mode
     * @return void
     */
    public function testIsWritable(string $mode): void
    {
        $handle = fopen('php://temp', $mode);
        $stream = new Stream($handle);

        $this->assertTrue($stream->isWritable());
    }

    /**
     * @return void
     */
    public function testWrite(): void
    {
        $handle = fopen('php://temp', 'wb+');
        $stream = new Stream($handle);

        $this->assertSame(4, $stream->write('data'));
    }

    /**
     * @return void
     */
    public function testWriteThrowsExceptionOnDetachedStream(): void
    {
        $handle = fopen('php://temp', 'wb+');
        $stream = new Stream($handle);
        $stream->detach();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Stream is detached');

        $stream->write('data');
    }

    /**
     * @return void
     */
    public function testWriteThrowsExceptionOnReadOnlyStream(): void
    {
        $handle = fopen('php://temp', 'rb');
        $stream = new Stream($handle);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Cannot write to a non-writable stream');

        $stream->write('data');
    }

    /**
     * @dataProvider readableModeProvider
     * @param  string $mode
     * @return void
     */
    public function testIsReadable(string $mode): void
    {
        $handle = fopen('php://temp', $mode);
        $stream = new Stream($handle);

        $this->assertTrue($stream->isReadable());
    }

    /**
     * @return void
     */
    public function testRead(): void
    {
        $handle = fopen('php://temp', 'rb+');
        $stream = new Stream($handle);
        $stream->write('data');
        $stream->rewind();

        $this->assertSame('data', $stream->read(4));
    }

    /**
     * @return void
     */
    public function testReadReturnsEmptyStringOnZeroLength(): void
    {
        $handle = fopen('php://temp', 'rb+');
        $stream = new Stream($handle);
        $stream->write('data');
        $stream->rewind();

        $this->assertSame('', $stream->read(0));
    }

    /**
     * @return void
     */
    public function testReadThrowsExceptionOnDetachedStream(): void
    {
        $handle = fopen('php://temp', 'rb+');
        $stream = new Stream($handle);
        $stream->detach();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Stream is detached');

        $stream->read(4);
    }

    /**
     * @return void
     */
    public function testReadThrowsExceptionOnWriteOnlyStream(): void
    {
        $handle = fopen('php://output', 'wb');
        $stream = new Stream($handle);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Cannot read from non-readable stream');

        $stream->read(4);
    }

    /**
     * @return void
     */
    public function testReadThrowsExceptionOnNegativeLength(): void
    {
        $handle = fopen('php://temp', 'wb');
        $stream = new Stream($handle);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Length parameter cannot be negative');

        $stream->read(-2);
    }

    /**
     * @return void
     */
    public function testGetContents(): void
    {
        $handle = fopen('php://temp', 'rb+');
        $stream = new Stream($handle);
        $stream->write('data');
        $stream->rewind();

        $this->assertSame('data', $stream->getContents());
    }

    /**
     * @return void
     */
    public function testGetContentsThrowsExceptionOnWriteOnlyStream(): void
    {
        $handle = fopen('php://output', 'wb');
        $stream = new Stream($handle);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Cannot read from non-readable stream');

        $stream->getContents();
    }

    /**
     * @return void
     */
    public function testGetContentsThrowsExceptionOnDetachedStream(): void
    {
        $handle = fopen('php://temp', 'rb+');
        $stream = new Stream($handle);
        $stream->detach();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Stream is detached');

        $stream->getContents();
    }

    /**
     * @return void
     */
    public function testGetMetadata(): void
    {
        $handle = fopen('php://temp', 'rb+');
        $stream = new Stream($handle);
        $metadata = $stream->getMetadata();

        $this->assertArrayHasKey('wrapper_type', $metadata);
        $this->assertArrayHasKey('stream_type', $metadata);
        $this->assertArrayHasKey('mode', $metadata);
        $this->assertArrayHasKey('unread_bytes', $metadata);
        $this->assertArrayHasKey('seekable', $metadata);
        $this->assertArrayHasKey('uri', $metadata);
        $this->assertSame('php://temp', $stream->getMetadata('uri'));

        $stream->detach();

        $this->assertNull($stream->getMetadata());
    }

    /**
     * @return void
     */
    public function testDoNotCloseResourceOnObjectDestruction(): void
    {
        $handle = fopen('php://temp', 'rb+');
        $stream = new Stream($handle);
        unset($stream);

        $this->assertTrue(is_resource($handle));
        fclose($handle);
    }

    /**
     * @return void
     */
    public function testWriteEmptyData(): void
    {
        $handle = fopen('php://temp', 'rb+');
        $stream = new Stream($handle);

        $this->expectException(RuntimeException::class);
        $stream->write('');
    }

    /**
     * @return void
     */
    public function testWriteSuppressesEngineWarningUnderThrowingErrorHandler(): void
    {
        $this->registerEngineWarningWrapper();
        $this->installThrowingErrorHandler();

        $stream = new Stream(fopen('predis-test-engine-warning://x', 'r+'));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Unable to write to stream');

        $stream->write('data');
    }

    /**
     * @return void
     */
    public function testReadSuppressesEngineWarningUnderThrowingErrorHandler(): void
    {
        $this->registerEngineWarningWrapper();
        $this->installThrowingErrorHandler();

        $stream = new Stream(fopen('predis-test-engine-warning://x', 'r+'));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Unable to read from stream');

        $stream->read(4);
    }

    /**
     * Mimics error handlers installed by Laravel, Symfony and Laminas, which
     * convert engine notices/warnings into thrown exceptions unless the call
     * site suppressed them with `@` (see GH-1725). The runner's own ambient
     * error_reporting() level is irrelevant to what we want to assert here,
     * so it's pinned to a known, fully-enabled value for the duration of the
     * test rather than trusted as-is.
     *
     * @return void
     */
    private function installThrowingErrorHandler(): void
    {
        $this->originalErrorReporting = error_reporting(E_ALL);

        set_error_handler(static function ($level, $message, $file = '', $line = 0) {
            if (error_reporting() & $level) {
                throw new ErrorException($message, 0, $level, $file, $line);
            }

            return false;
        });

        $this->registeredErrorHandler = true;
    }

    /**
     * @var bool
     */
    private $registeredErrorHandler = false;

    /**
     * @var int|null
     */
    private $originalErrorReporting;

    /**
     * @return void
     */
    protected function tearDown(): void
    {
        if ($this->registeredErrorHandler) {
            restore_error_handler();
            error_reporting($this->originalErrorReporting);
            $this->registeredErrorHandler = false;
        }
    }

    /**
     * @return void
     */
    private function registerEngineWarningWrapper(): void
    {
        if (!in_array('predis-test-engine-warning', stream_get_wrappers(), true)) {
            stream_wrapper_register('predis-test-engine-warning', EngineWarningStreamWrapperFixture::class);
        }
    }

    public function writableModeProvider(): array
    {
        return [
            ['w'],
            ['w+'],
            ['rw'],
            ['r+'],
            ['x+'],
            ['c+'],
            ['wb'],
            ['w+b'],
            ['r+b'],
            ['rb+'],
            ['x+b'],
            ['c+b'],
            ['w+t'],
            ['r+t'],
            ['x+t'],
            ['c+t'],
            ['a'],
            ['a+'],
        ];
    }

    public function readableModeProvider(): iterable
    {
        return [
            ['r'],
            ['w+'],
            ['r+'],
            ['x+'],
            ['c+'],
            ['rb'],
            ['w+b'],
            ['r+b'],
            ['x+b'],
            ['c+b'],
            ['rt'],
            ['w+t'],
            ['r+t'],
            ['x+t'],
            ['c+t'],
            ['a+'],
            ['rb+'],
        ];
    }
}

/**
 * Stream wrapper fixture that raises an engine-style warning from
 * stream_write()/stream_read(), used to verify that Stream::write()/read()
 * suppress it instead of letting a host-installed error handler turn it
 * into an uncaught exception (see GH-1725).
 */
class EngineWarningStreamWrapperFixture
{
    /**
     * @var resource
     */
    public $context;

    public function stream_open($path, $mode, $options, &$openedPath): bool
    {
        return true;
    }

    /**
     * @return int|bool
     */
    public function stream_write(string $data)
    {
        trigger_error('fwrite(): synthetic broken pipe', E_USER_WARNING);

        return false;
    }

    /**
     * @return string|bool
     */
    public function stream_read(int $count)
    {
        trigger_error('fread(): synthetic broken pipe', E_USER_WARNING);

        return false;
    }

    public function stream_eof(): bool
    {
        return false;
    }

    public function stream_stat()
    {
        return [];
    }

    public function stream_close(): void
    {
    }
}
