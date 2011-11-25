<?php

/*
 * This file is part of the Predis package.
 *
 * (c) Daniele Alessandri <suppakilla@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Network;

use Predis\IConnectionParameters;
use Predis\Commands\ICommand;
use Predis\Protocol\IProtocolProcessor;
use Predis\Protocol\Text\TextProtocol;

/**
 * Connection abstraction to Redis servers based on PHP's stream that uses an
 * external protocol processor defining the protocol used for the communication.
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class ComposableStreamConnection extends StreamConnection implements IConnectionComposable
{
    private $protocol;

    /**
     * @param IConnectionParameters $parameters Parameters used to initialize the connection.
     * @param IProtocolProcessor $protocol A protocol processor.
     */
    public function __construct(IConnectionParameters $parameters, IProtocolProcessor $protocol = null)
    {
        $this->setProtocol($protocol ?: new TextProtocol());

        parent::__construct($parameters);
    }

    /**
     * {@inheritdoc}
     */
    protected function initializeProtocol(IConnectionParameters $parameters)
    {
        $this->protocol->setOption('throw_errors', $parameters->throw_errors);
        $this->protocol->setOption('iterable_multibulk', $parameters->iterable_multibulk);
    }

    /**
     * {@inheritdoc}
     */
    public function setProtocol(IProtocolProcessor $protocol)
    {
        if ($protocol === null) {
            throw new \InvalidArgumentException("The protocol instance cannot be a null value");
        }
        $this->protocol = $protocol;
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
    public function writeBytes($buffer)
    {
        parent::writeBytes($buffer);
    }

    /**
     * {@inheritdoc}
     */
    public function readBytes($length)
    {
        if ($length <= 0) {
            throw new \InvalidArgumentException('Length parameter must be greater than 0');
        }

        $value  = '';
        $socket = $this->getResource();

        do {
            $chunk = fread($socket, $length);
            if ($chunk === false || $chunk === '') {
                $this->onConnectionError('Error while reading bytes from the server');
            }
            $value .= $chunk;
        }
        while (($length -= strlen($chunk)) > 0);

        return $value;
    }

    /**
     * {@inheritdoc}
     */
    public function readLine()
    {
        $value  = '';
        $socket = $this->getResource();

        do {
            $chunk = fgets($socket);
            if ($chunk === false || $chunk === '') {
                $this->onConnectionError('Error while reading line from the server');
            }
            $value .= $chunk;
        }
        while (substr($value, -2) !== "\r\n");

        return substr($value, 0, -2);
    }

    /**
     * {@inheritdoc}
     */
    public function writeCommand(ICommand $command)
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
}
