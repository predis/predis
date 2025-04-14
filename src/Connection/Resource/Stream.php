<?php

/*
 * This file is part of the Predis package.
 *
 * (c) 2009-2020 Daniele Alessandri
 * (c) 2021-2025 Till Krüss
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Connection\Resource;

use InvalidArgumentException;
use Psr\Http\Message\StreamInterface;
use RuntimeException;

class Stream implements StreamInterface
{
    /**
     * @see https://www.php.net/manual/en/function.fopen.php
     * @see https://www.php.net/manual/en/function.gzopen.php
     */
    private const READABLE_MODES = '/r|a\+|ab\+|w\+|wb\+|x\+|xb\+|c\+|cb\+/';
    private const WRITABLE_MODES = '/a|w|r\+|rb\+|rw|x|c/';

    /**
     * @var resource
     */
    private $stream;

    /**
     * @var bool
     */
    private $seekable;

    /**
     * @var bool
     */
    private $readable;

    /**
     * @var bool
     */
    private $writable;

    /**
     * @param  resource                 $stream
     * @throws InvalidArgumentException if stream is not a valid resource.
     */
    public function __construct($stream)
    {
        if (!is_resource($stream)) {
            throw new InvalidArgumentException('Given stream is not a valid resource');
        }

        $this->stream = $stream;
        $metadata = stream_get_meta_data($this->stream);
        $this->seekable = $metadata['seekable'];
        $this->readable = (bool) preg_match(self::READABLE_MODES, $metadata['mode']);
        $this->writable = (bool) preg_match(self::WRITABLE_MODES, $metadata['mode']);
    }

    /**
     * {@inheritDoc}
     */
    public function __toString(): string
    {
        if ($this->isSeekable()) {
            $this->seek(0);
        }

        return $this->getContents();
    }

    /**
     * {@inheritDoc}
     */
    public function close(): void
    {
        if (isset($this->stream)) {
            fclose($this->stream);
        }

        $this->detach();
    }

    /**
     * {@inheritDoc}
     */
    public function detach()
    {
        if (!isset($this->stream)) {
            return null;
        }

        $result = $this->stream;
        unset($this->stream);
        $this->readable = $this->writable = $this->seekable = false;

        return $result;
    }

    /**
     * {@inheritDoc}
     */
    public function getSize(): ?int
    {
        if (!isset($this->stream)) {
            return null;
        }

        $stats = fstat($this->stream);
        if (is_array($stats) && isset($stats['size'])) {
            return $stats['size'];
        }

        return null;
    }

    /**
     * {@inheritDoc}
     */
    public function tell(): int
    {
        if (!isset($this->stream)) {
            throw new RuntimeException('Stream is detached');
        }

        $result = ftell($this->stream);

        if ($result === false) {
            throw new RuntimeException('Unable to determine stream position');
        }

        return $result;
    }

    /**
     * {@inheritDoc}
     */
    public function eof(): bool
    {
        if (!isset($this->stream)) {
            throw new RuntimeException('Stream is detached');
        }

        return feof($this->stream);
    }

    /**
     * {@inheritDoc}
     */
    public function isSeekable(): bool
    {
        return $this->seekable;
    }

    /**
     * {@inheritDoc}
     */
    public function seek(int $offset, int $whence = SEEK_SET): void
    {
        if (!isset($this->stream)) {
            throw new RuntimeException('Stream is detached');
        }

        if (!$this->isSeekable()) {
            throw new RuntimeException('Stream is not seekable');
        }

        if (fseek($this->stream, $offset, $whence) === -1) {
            throw new RuntimeException("Unable to seek stream from offset {$offset} to whence {$whence}");
        }
    }

    /**
     * {@inheritDoc}
     */
    public function rewind(): void
    {
        $this->seek(0);
    }

    /**
     * {@inheritDoc}
     */
    public function isWritable(): bool
    {
        return $this->writable;
    }

    /**
     * {@inheritDoc}
     * @throws RuntimeException
     */
    public function write(string $string): int
    {
        if (!isset($this->stream)) {
            throw new RuntimeException('Stream is detached');
        }

        if (!$this->isWritable()) {
            throw new RuntimeException('Cannot write to a non-writable stream');
        }

        $result = fwrite($this->stream, $string);

        if ($result === false) {
            throw new RuntimeException('Unable to write to stream', 1);
        }

        return $result;
    }

    /**
     * {@inheritDoc}
     */
    public function isReadable(): bool
    {
        return $this->readable;
    }

    /**
     * {@inheritDoc}
     * @param  int              $length If length = -1, reads a stream line by line (e.g fgets())
     * @throws RuntimeException
     */
    public function read(int $length): string
    {
        if (!isset($this->stream)) {
            throw new RuntimeException('Stream is detached');
        }

        if (!$this->isReadable()) {
            throw new RuntimeException('Cannot read from non-readable stream');
        }

        if ($length < -1) {
            throw new RuntimeException('Length parameter cannot be negative');
        }

        if (0 === $length) {
            return '';
        }

        if ($length === -1) {
            $string = fgets($this->stream);
        } else {
            $string = fread($this->stream, $length);
        }

        if (false === $string) {
            throw new RuntimeException('Unable to read from stream', 1);
        }

        return $string;
    }

    /**
     * {@inheritDoc}
     */
    public function getContents(): string
    {
        if (!isset($this->stream)) {
            throw new RuntimeException('Stream is detached');
        }

        if (!$this->isReadable()) {
            throw new RuntimeException('Cannot read from non-readable stream');
        }

        return stream_get_contents($this->stream);
    }

    /**
     * {@inheritDoc}
     */
    public function getMetadata(?string $key = null)
    {
        if (!isset($this->stream)) {
            return null;
        }

        if (!$key) {
            return stream_get_meta_data($this->stream);
        }

        $metadata = stream_get_meta_data($this->stream);

        return $metadata[$key] ?? null;
    }
}
