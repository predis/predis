<?php
/*
This class provides the implementation of a Predis connection that internally
uses the PHP socket extension for network communication and wraps the phpiredis
C extension (PHP bindings for hiredis) to parse the Redis protocol. Everything
is *highly experimental* (even the very same phpiredis since it is quite new),
so use it at your own risk.

This class is mainly intended to provide an optional low-overhead alternative
for processing replies from Redis compared to the standard pure-PHP classes.
Differences in speed when dealing with short inline replies are practically
nonexistent, the actual speed boost is for long multibulk replies when this
protocol processor can parse and return replies very fast.

For instructions on how to build and install the phpiredis extension, please
consult the repository of the project at http://github.com/seppo0010/phpiredis
*/

namespace Predis\Network;

use Predis\ResponseError;
use Predis\ResponseQueued;
use Predis\ClientException;
use Predis\ServerException;
use Predis\ConnectionParameters;
use Predis\Commands\ICommand;

class PhpiredisConnection extends ConnectionBase {
    private $_reader;

    public function __construct(ConnectionParameters $parameters) {
        if (!function_exists('socket_create')) {
            throw new ClientException(
                'The socket extension must be loaded in order to be able to ' .
                'use this connection class'
            );
        }
        parent::__construct($parameters);
    }

    public function __destruct() {
        phpiredis_reader_destroy($this->_reader);
        parent::__destruct();
    }

    protected function checkParameters(ConnectionParameters $parameters) {
        if (isset($parameters->iterable_multibulk)) {
            $this->onInvalidOption('iterable_multibulk', $parameters);
        }
        if (isset($parameters->connection_persistent)) {
            $this->onInvalidOption('connection_persistent', $parameters);
        }
        return parent::checkParameters($parameters);
    }

    private function initializeReader($throw_errors = true) {
        if (!function_exists('phpiredis_reader_create')) {
            throw new ClientException(
                'The phpiredis extension must be loaded in order to be able to ' .
                'use this connection class'
            );
        }
        $reader = phpiredis_reader_create();
        phpiredis_reader_set_status_handler($reader, $this->getStatusHandler());
        phpiredis_reader_set_error_handler($reader, $this->getErrorHandler($throw_errors));
        $this->_reader = $reader;
    }

    protected function initializeProtocol(ConnectionParameters $parameters) {
        $this->initializeReader($parameters->throw_errors);
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

    private function getErrorHandler($throwErrors = true) {
        if ($throwErrors) {
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

    private function getAddress(ConnectionParameters $parameters) {
        if ($parameters->scheme === 'unix') {
            return $parameters->path;
        }
        $host = $parameters->host;
        if (ip2long($host) === false) {
            if (($address = gethostbyname($host)) === $host) {
                $this->onCommunicationException("Cannot resolve the address of $host");
            }
            return $address;
        }
        return $host;
    }

    private function connectWithTimeout(ConnectionParameters $parameters) {
        $host = self::getAddress($parameters);
        $socket = $this->getResource();
        socket_set_nonblock($socket);
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
            socket_close($this->getResource());
            parent::disconnect();
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
                return;
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
        array_unshift($cmdargs, $command->getId());
        $this->write(phpiredis_format_command($cmdargs));
    }

    public function readResponse(ICommand $command) {
        $reply = $this->read();
        return isset($reply->skipParse) ? $reply : $command->parseResponse($reply);
    }
}
