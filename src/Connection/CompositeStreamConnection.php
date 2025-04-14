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

namespace Predis\Connection;

use InvalidArgumentException;
use Predis\Command\CommandInterface;
use Predis\Protocol\ProtocolProcessorInterface;
use Predis\Protocol\Text\ProtocolProcessor as TextProtocolProcessor;
use Psr\Http\Message\StreamInterface;
use RuntimeException;

/**
 * Connection abstraction to Redis servers based on PHP's stream that uses an
 * external protocol processor defining the protocol used for the communication.
 *
 * @method StreamInterface getResource()
 */
class CompositeStreamConnection extends StreamConnection implements CompositeConnectionInterface
{
    protected $protocol;

    /**
     * @param ParametersInterface             $parameters Initialization parameters for the connection.
     * @param ProtocolProcessorInterface|null $protocol   Protocol processor.
     */
    public function __construct(
        ParametersInterface $parameters,
        ?ProtocolProcessorInterface $protocol = null
    ) {
        parent::__construct($parameters);
        $this->protocol = $protocol ?: new TextProtocolProcessor();
    }

    /**
     * {@inheritdoc}
     */
    public function getProtocol()
    {
        return $this->protocol;
    }

    /**
     * {@inheritdoc}
     */
    public function writeBuffer($buffer)
    {
        $this->write($buffer);
    }

    /**
     * {@inheritdoc}
     */
    public function readBuffer($length)
    {
        if ($length <= 0) {
            throw new InvalidArgumentException('Length parameter must be greater than 0.');
        }

        $value = '';
        $stream = $this->getResource();

        if ($stream->eof()) {
            $this->onStreamError(new RuntimeException('Stream is already at the end'), '');
        }

        do {
            try {
                $chunk = $stream->read($length);
            } catch (RuntimeException $e) {
                $this->onStreamError($e, 'Error while reading bytes from the server.');
            }

            $value .= $chunk; // @phpstan-ignore-line
        } while (($length -= strlen($chunk)) > 0); // @phpstan-ignore-line

        return $value;
    }

    /**
     * {@inheritdoc}
     */
    public function readLine()
    {
        $value = '';
        $stream = $this->getResource();

        if ($stream->eof()) {
            $this->onStreamError(new RuntimeException('Stream is already at the end'), '');
        }

        do {
            try {
                $chunk = $stream->read(-1);
            } catch (RuntimeException $e) {
                $this->onStreamError($e, 'Error while reading bytes from the server.');
            }

            $value .= $chunk; // @phpstan-ignore-line
        } while (substr($value, -2) !== "\r\n");

        return substr($value, 0, -2);
    }

    /**
     * {@inheritdoc}
     */
    public function writeRequest(CommandInterface $command)
    {
        $this->protocol->write($this, $command);
    }

    /**
     * {@inheritdoc}
     */
    public function read()
    {
        return $this->protocol->read($this);
    }

    /**
     * {@inheritdoc}
     */
    public function __sleep()
    {
        return array_merge(parent::__sleep(), ['protocol']);
    }
}
