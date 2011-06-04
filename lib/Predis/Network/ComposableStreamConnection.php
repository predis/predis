<?php

namespace Predis\Network;

use Predis\IConnectionParameters;
use Predis\Commands\ICommand;
use Predis\Protocol\IProtocolProcessor;
use Predis\Protocol\Text\TextProtocol;

class ComposableStreamConnection extends StreamConnection implements IConnectionComposable {
    private $_protocol;

    public function __construct(IConnectionParameters $parameters, IProtocolProcessor $protocol = null) {
        $this->setProtocol($protocol ?: new TextProtocol());
        parent::__construct($parameters);
    }

    protected function initializeProtocol(IConnectionParameters $parameters) {
        $this->_protocol->setOption('throw_errors', $parameters->throw_errors);
        $this->_protocol->setOption('iterable_multibulk', $parameters->iterable_multibulk);
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

    public function writeBytes($buffer) {
        parent::writeBytes($buffer);
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
                $this->onConnectionError('Error while reading bytes from the server');
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
                $this->onConnectionError('Error while reading line from the server');
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
