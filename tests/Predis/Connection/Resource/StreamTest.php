<?php

/*
 * This file is part of the Predis package.
 *
 * (c) 2009-2020 Daniele Alessandri
 * (c) 2021-2023 Till Krüss
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Connection\Resource;

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
