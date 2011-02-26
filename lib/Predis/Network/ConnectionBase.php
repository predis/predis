<?php

namespace Predis\Network;

use Predis\Utils;
use Predis\ICommand;
use Predis\ConnectionParameters;
use Predis\ClientException;
use Predis\CommunicationException;
use Predis\Protocols\IRedisProtocol;

abstract class ConnectionBase implements IConnectionSingle {
    private $_cachedId;
    protected $_params, $_socket, $_initCmds, $_protocol;

    public function __construct(ConnectionParameters $parameters, IRedisProtocol $protocol) {
        $this->_initCmds = array();
        $this->_params   = $parameters;
        $this->_protocol = $protocol;
    }

    public function __destruct() {
        $this->disconnect();
    }

    public function isConnected() {
        return is_resource($this->_socket);
    }

    protected abstract function createResource();

    public function connect() {
        if ($this->isConnected()) {
            throw new ClientException('Connection already estabilished');
        }
        $this->createResource();
    }

    public function disconnect() {
        if ($this->isConnected()) {
            fclose($this->_socket);
        }
    }

    public function pushInitCommand(ICommand $command){
        $this->_initCmds[] = $command;
    }

    public function executeCommand(ICommand $command) {
        $this->writeCommand($command);
        if ($command->closesConnection()) {
            return $this->disconnect();
        }
        return $this->readResponse($command);
    }

    protected function onCommunicationException($message, $code = null) {
        Utils::onCommunicationException(
            new CommunicationException($this, $message, $code)
        );
    }

    public function getResource() {
        if (!$this->isConnected()) {
            $this->connect();
        }
        return $this->_socket;
    }

    public function getParameters() {
        return $this->_params;
    }

    public function getProtocol() {
        return $this->_protocol;
    }

    public function setProtocol(IRedisProtocol $protocol) {
        if ($protocol === null) {
            throw new \InvalidArgumentException("The protocol instance cannot be a null value");
        }
        $this->_protocol = $protocol;
    }

    public function __toString() {
        if (!isset($this->_cachedId)) {
            $this->_cachedId = "{$this->_params->host}:{$this->_params->port}";
        }
        return $this->_cachedId;
    }
}
