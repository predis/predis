<?php

namespace Predis\Network;

use \InvalidArgumentException;
use Predis\Utils;
use Predis\ConnectionParameters;
use Predis\ClientException;
use Predis\CommunicationException;
use Predis\Commands\ICommand;

abstract class ConnectionBase implements IConnectionSingle {
    private $_cachedId, $_resource;
    protected $_params, $_initCmds;

    public function __construct(ConnectionParameters $parameters) {
        $this->_initCmds = array();
        $this->_params = $this->checkParameters($parameters);
        $this->initializeProtocol($parameters);
    }

    public function __destruct() {
        $this->disconnect();
    }

    protected function checkParameters(ConnectionParameters $parameters) {
        switch ($parameters->scheme) {
            case 'unix':
                $pathToSocket = $parameters->path;
                if (!isset($pathToSocket)) {
                    throw new InvalidArgumentException('Missing UNIX domain socket path');
                }
                if (!file_exists($pathToSocket)) {
                    throw new InvalidArgumentException("Could not find $pathToSocket");
                }
            case 'tcp':
                return $parameters;
            default:
                throw new InvalidArgumentException("Invalid scheme: {$parameters->scheme}");
        }
        return $parameters;
    }

    protected function initializeProtocol(ConnectionParameters $parameters) {
        $this->setProtocolOption('throw_errors', $parameters->throw_errors);
        $this->setProtocolOption('iterable_multibulk', $parameters->iterable_multibulk);
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

    protected function onCommunicationException($message, $code = null) {
        Utils::onCommunicationException(
            new CommunicationException($this, $message, $code)
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

    public function __toString() {
        if (!isset($this->_cachedId)) {
            $this->_cachedId = "{$this->_params->host}:{$this->_params->port}";
        }
        return $this->_cachedId;
    }
}
