<?php

namespace Predis\Network;

use \InvalidArgumentException;
use Predis\Helpers;
use Predis\IReplyObject;
use Predis\IConnectionParameters;
use Predis\ClientException;
use Predis\Commands\ICommand;
use Predis\Protocol\ProtocolException;

abstract class ConnectionBase implements IConnectionSingle {
    private $_resource;
    private $_cachedId;
    protected $_params;
    protected $_initCmds;

    public function __construct(IConnectionParameters $parameters) {
        $this->_initCmds = array();
        $this->_params = $this->checkParameters($parameters);
        $this->initializeProtocol($parameters);
    }

    public function __destruct() {
        $this->disconnect();
    }

    protected function checkParameters(IConnectionParameters $parameters) {
        switch ($parameters->scheme) {
            case 'unix':
                if (!isset($parameters->path)) {
                    throw new InvalidArgumentException('Missing UNIX domain socket path');
                }
            case 'tcp':
                return $parameters;
            default:
                throw new InvalidArgumentException("Invalid scheme: {$parameters->scheme}");
        }
    }

    protected function initializeProtocol(IConnectionParameters $parameters) {
        // NOOP
    }

    protected abstract function createResource();

    public function isConnected() {
        return isset($this->_resource);
    }

    public function connect() {
        if ($this->isConnected()) {
            throw new ClientException('Connection already estabilished');
        }
        $this->_resource = $this->createResource();
    }

    public function disconnect() {
        unset($this->_resource);
    }

    public function pushInitCommand(ICommand $command) {
        $this->_initCmds[] = $command;
    }

    public function executeCommand(ICommand $command) {
        $this->writeCommand($command);
        return $this->readResponse($command);
    }

    public function readResponse(ICommand $command) {
        $reply = $this->read();
        if ($reply instanceof IReplyObject) {
            return $reply;
        }
        return $command->parseResponse($reply);
    }

    protected function onConnectionError($message, $code = null) {
        Helpers::onCommunicationException(
            new ConnectionException($this, $message, $code)
        );
    }

    protected function onProtocolError($message) {
        Helpers::onCommunicationException(
            new ProtocolException($this, $message)
        );
    }

    protected function onInvalidOption($option, $parameters = null) {
        $message = "Invalid option: $option";
        if (isset($parameters)) {
            $message .= " [$parameters]";
        }
        throw new InvalidArgumentException($message);
    }

    public function getResource() {
        if (isset($this->_resource)) {
            return $this->_resource;
        }
        $this->connect();
        return $this->_resource;
    }

    public function getParameters() {
        return $this->_params;
    }

    protected function getIdentifier() {
        if ($this->_params->scheme === 'unix') {
            return $this->_params->path;
        }
        return "{$this->_params->host}:{$this->_params->port}";
    }

    public function __toString() {
        if (!isset($this->_cachedId)) {
            $this->_cachedId = $this->getIdentifier();
        }
        return $this->_cachedId;
    }
}
