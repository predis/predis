<?php

namespace Predis\Network;

use Predis\ConnectionParameters;
use Predis\CommunicationException;
use Predis\Commands\ICommand;
use Predis\Protocols\IProtocolProcessor;
use Predis\Protocols\TextProtocol;

class ComposableStreamConnection extends StreamConnection implements IConnectionComposable {
    private $_protocol;

    public function __construct(ConnectionParameters $parameters, IProtocolProcessor $protocol = null) {
        $this->setProtocol($protocol ?: new TextProtocol());
        parent::__construct($parameters);
    }

    public function setProtocol(IProtocolProcessor $protocol) {
        if ($protocol === null) {
            throw new \InvalidArgumentException("The protocol instance cannot be a null value");
        }
        $this->_protocol = $protocol;
    }

    public function getProtocol() {
        return $this->_protocol;
    }

    protected function setProtocolOption($option, $value) {
        return $this->_protocol->setOption($option, $value);
    }

    public function writeBytes($value) {
        $socket = $this->getResource();
        while (($length = strlen($value)) > 0) {
            $written = fwrite($socket, $value);
            if ($length === $written) {
                return true;
            }
            if ($written === false || $written === 0) {
                $this->onCommunicationException('Error while writing bytes to the server');
            }
            $value = substr($value, $written);
        }
        return true;
    }

    public function readBytes($length) {
        if ($length <= 0) {
            throw new \InvalidArgumentException('Length parameter must be greater than 0');
        }
        $socket = $this->getResource();
        $value  = '';
        do {
            $chunk = fread($socket, $length);
            if ($chunk === false || $chunk === '') {
                $this->onCommunicationException('Error while reading bytes from the server');
            }
            $value .= $chunk;
        }
        while (($length -= strlen($chunk)) > 0);
        return $value;
    }

    public function readLine() {
        $socket = $this->getResource();
        $value  = '';
        do {
            $chunk = fgets($socket);
            if ($chunk === false || $chunk === '') {
                $this->onCommunicationException('Error while reading line from the server');
            }
            $value .= $chunk;
        }
        while (substr($value, -2) !== "\r\n");
        return substr($value, 0, -2);
    }

    public function writeCommand(ICommand $command) {
        $this->_protocol->write($this, $command);
    }

    public function read() {
        return $this->_protocol->read($this);
    }
}
