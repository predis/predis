<?php

namespace Predis\Network;

use Predis\ICommand;
use Predis\ResponseError;
use Predis\ResponseQueued;
use Predis\ServerException;
use Predis\ConnectionParameters;

class PhpiredisConnection extends ConnectionBase {
    private $_reader, $_throwErrors;

    public function __construct(ConnectionParameters $parameters) {
        parent::__construct($this->checkParameters($parameters));
        $this->_throwErrors = true;
        $this->initializeReader();
    }

    public function __destruct() {
        $this->disconnect();
        phpiredis_reader_destroy($this->_reader);
    }

    protected function checkParameters(ConnectionParameters $parameters) {
        switch ($parameters->scheme) {
            case 'unix':
                $pathToSocket = $parameters->path;
                if (!isset($pathToSocket)) {
                    throw new \InvalidArgumentException('Missing UNIX domain socket path');
                }
                if (!file_exists($pathToSocket)) {
                    throw new \InvalidArgumentException("Could not find $pathToSocket");
                }
            case 'tcp':
                return $parameters;
            default:
                throw new \InvalidArgumentException("Invalid scheme: {$parameters->scheme}");
        }
        if ($parameters->connection_persistent == true) {
            throw new \InvalidArgumentException(
                'Persistent connections are not supported by this connection class.'
            );
        }
    }

    private function initializeReader() {
        if (!function_exists('phpiredis_reader_create')) {
            throw new ClientException(
                'The phpiredis extension must be loaded in order to ' .
                'be able to use this protocol processor'
            );
        }
        $this->_reader = phpiredis_reader_create();
        phpiredis_reader_set_status_handler($this->_reader, $this->getStatusHandler());
        phpiredis_reader_set_error_handler($this->_reader, $this->getErrorHandler());
    }

    private function getStatusHandler() {
        return function($payload) {
            switch ($payload) {
                case 'OK':
                    return true;
                case 'QUEUED':
                    return new ResponseQueued();
                default:
                    return $payload;
            }
        };
    }

    private function getErrorHandler() {
        if ($this->_throwErrors) {
            return function($errorMessage) {
                throw new ServerException(substr($errorMessage, 4));
            };
        }
        return function($errorMessage) {
            return new ResponseError(substr($errorMessage, 4));
        };
    }

    private function emitSocketError() {
        $errno  = socket_last_error();
        $errstr = socket_strerror($errno);
        $this->disconnect();
        $this->onCommunicationException(trim($errstr), $errno);
    }

    protected function createResource() {
        $parameters = $this->_params;
        $initializer = array($this, "{$parameters->scheme}SocketInitializer");
        $socket = call_user_func($initializer, $parameters);
        $this->setSocketOptions($socket, $parameters);
        return $socket;
    }

    private function tcpSocketInitializer(ConnectionParameters $parameters) {
        $socket = @socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        if (!is_resource($socket)) {
            $this->emitSocketError();
        }
        $addressLong = ip2long($parameters->host);
        if ($addressLong == -1 || $addressLong === false) {
            $host = gethostbyname($parameters->host);
        }
        return $socket;
    }

    private function unixSocketInitializer(ConnectionParameters $parameters) {
        $socket = @socket_create(AF_UNIX, SOCK_STREAM, 0);
        if (!is_resource($socket)) {
            $this->emitSocketError();
        }
        return $socket;
    }

    private function setSocketOptions($socket, ConnectionParameters $parameters) {
        if ($parameters->scheme !== 'tcp') {
            return;
        }
        if (!socket_set_option($socket, SOL_TCP, TCP_NODELAY, 1)) {
            $this->emitSocketError();
        }
        if (!socket_set_option($socket, SOL_SOCKET, SO_REUSEADDR, 1)) {
            $this->emitSocketError();
        }
        if (isset($parameters->read_write_timeout)) {
            $rwtimeout = $parameters->read_write_timeout;
            $timeoutSec  = floor($rwtimeout);
            $timeoutUsec = ($rwtimeout - $timeoutSec) * 1000000;
            $timeout = array('sec' => $timeoutSec, 'usec' => $timeoutUsec);
            if (!socket_set_option($socket, SOL_SOCKET, SO_SNDTIMEO, $timeout)) {
                $this->emitSocketError();
            }
            if (!socket_set_option($socket, SOL_SOCKET, SO_RCVTIMEO, $timeout)) {
                $this->emitSocketError();
            }
        }
    }

    private function connectWithTimeout(ConnectionParameters $parameters) {
        $socket = $this->_resource;
        socket_set_nonblock($socket);
        $host = $parameters->scheme === 'unix' ? $parameters->path : $parameters->host;
        if (@socket_connect($socket, $host, $parameters->port) === false) {
            $error = socket_last_error();
            if ($error != SOCKET_EINPROGRESS && $error != SOCKET_EALREADY) {
                $this->emitSocketError();
            }
        }
        socket_set_block($socket);

        $null = null;
        $selectable = array($socket);
        $timeout = $parameters->connection_timeout;
        $timeoutSecs = floor($timeout);
        $timeoutUSecs = ($timeout - $timeoutSecs) * 1000000;

        $selected = socket_select($selectable, $selectable, $null, $timeoutSecs, $timeoutUSecs);
        if ($selected === 2) {
            $this->onCommunicationException('Connection refused', SOCKET_ECONNREFUSED);
        }
        if ($selected === 0) {
            $this->onCommunicationException('Connection timed out', SOCKET_ETIMEDOUT);
        }
        if ($selected === false) {
            $this->emitSocketError();
        }
    }

    public function connect() {
        parent::connect();
        $this->connectWithTimeout($this->_params);
        if (count($this->_initCmds) > 0) {
            $this->sendInitializationCommands();
        }
    }

    public function disconnect() {
        if ($this->isConnected()) {
            socket_close($this->_resource);
            $this->_resource = null;
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

    private function write($buffer) {
        $socket = $this->getResource();
        while (($length = strlen($buffer)) > 0) {
            $written = socket_write($socket, $buffer, $length);
            if ($length === $written) {
                return true;
            }
            if ($written === false) {
                $this->onCommunicationException('Error while writing bytes to the server');
            }
            $buffer = substr($buffer, $written);
        }
    }

    public function read() {
        $socket = $this->getResource();
        $reader = $this->_reader;
        while (($state = phpiredis_reader_get_state($reader)) === PHPIREDIS_READER_STATE_INCOMPLETE) {
            if (@socket_recv($socket, $buffer, 4096, 0) === false || $buffer === '') {
                $this->emitSocketError();
            }
            phpiredis_reader_feed($reader, $buffer);
        }
        if ($state === PHPIREDIS_READER_STATE_COMPLETE) {
            return phpiredis_reader_get_reply($reader);
        }
        else {
            $this->onCommunicationException(phpiredis_reader_get_error($reader));
        }
    }

    public function writeCommand(ICommand $command) {
        $cmdargs = $command->getArguments();
        array_unshift($cmdargs, $command->getCommandId());
        $this->write(phpiredis_format_command($cmdargs));
    }

    public function readResponse(ICommand $command) {
        $reply = $this->read();
        return isset($reply->skipParse) ? $reply : $command->parseResponse($reply);
    }

    public function setProtocolOption($option, $value) {
        switch ($option) {
            case 'iterable_multibulk':
                // TODO: iterable multibulk replies cannot be supported
                break;
            case 'throw_errors':
                $this->_throwErrors = (bool) $value;
                phpiredis_reader_set_error_handler($this->_reader, $this->getErrorHandler());
                break;
        }
    }
}
