<?php

namespace Predis\Network;

use Predis\ICommand;
use Predis\ConnectionParameters;
use Predis\CommunicationException;
use Predis\Protocols\IRedisProtocol;
use Predis\Protocols\TextProtocol;

class TcpConnection extends ConnectionBase implements IConnectionSingle {
    public function __construct(ConnectionParameters $parameters, IRedisProtocol $protocol = null) {
        parent::__construct($this->checkParameters($parameters), $protocol ?: new TextProtocol());
    }

    public function __destruct() {
        if (!$this->_params->connection_persistent) {
            $this->disconnect();
        }
    }

    protected function checkParameters(ConnectionParameters $parameters) {
        $scheme = $parameters->scheme;
        if ($scheme != 'tcp' && $scheme != 'redis') {
            throw new \InvalidArgumentException("Invalid scheme: {$scheme}");
        }
        return $parameters;
    }

    protected function createResource() {
        $uri = sprintf('tcp://%s:%d/', $this->_params->host, $this->_params->port);
        $connectFlags = STREAM_CLIENT_CONNECT;
        if ($this->_params->connection_async) {
            $connectFlags |= STREAM_CLIENT_ASYNC_CONNECT;
        }
        if ($this->_params->connection_persistent) {
            $connectFlags |= STREAM_CLIENT_PERSISTENT;
        }
        $this->_socket = @stream_socket_client(
            $uri, $errno, $errstr, $this->_params->connection_timeout, $connectFlags
        );

        if (!$this->_socket) {
            $this->onCommunicationException(trim($errstr), $errno);
        }

        if (isset($this->_params->read_write_timeout)) {
            $timeoutSeconds  = floor($this->_params->read_write_timeout);
            $timeoutUSeconds = ($this->_params->read_write_timeout - $timeoutSeconds) * 1000000;
            stream_set_timeout($this->_socket, $timeoutSeconds, $timeoutUSeconds);
        }
    }

    private function sendInitializationCommands() {
        foreach ($this->_initCmds as $command) {
            $this->writeCommand($command);
        }
        foreach ($this->_initCmds as $command) {
            $this->readResponse($command);
        }
    }

    public function connect() {
        parent::connect();
        if (count($this->_initCmds) > 0){
            $this->sendInitializationCommands();
        }
    }

    public function writeCommand(ICommand $command) {
        $this->_protocol->write($this, $command);
    }

    public function readResponse(ICommand $command) {
        $response = $this->_protocol->read($this);
        return isset($response->skipParse) ? $response : $command->parseResponse($response);
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
}
