<?php

namespace Predis\Network;

use Predis\ResponseError;
use Predis\ResponseQueued;
use Predis\ServerException;
use Predis\IConnectionParameters;
use Predis\Commands\ICommand;
use Predis\Iterators\MultiBulkResponseSimple;

class StreamConnection extends ConnectionBase {
    private $_mbiterable;
    private $_throwErrors;

    public function __destruct() {
        if (!$this->_params->connection_persistent) {
            $this->disconnect();
        }
    }

    protected function initializeProtocol(IConnectionParameters $parameters) {
        $this->_throwErrors = $parameters->throw_errors;
        $this->_mbiterable = $parameters->iterable_multibulk;
    }

    protected function createResource() {
        $parameters = $this->_params;
        $initializer = "{$parameters->scheme}StreamInitializer";
        return $this->$initializer($parameters);
    }

    private function tcpStreamInitializer(IConnectionParameters $parameters) {
        $uri = "tcp://{$parameters->host}:{$parameters->port}/";
        $flags = STREAM_CLIENT_CONNECT;
        if ($parameters->connection_async) {
            $flags |= STREAM_CLIENT_ASYNC_CONNECT;
        }
        if ($parameters->connection_persistent) {
            $flags |= STREAM_CLIENT_PERSISTENT;
        }
        $resource = @stream_socket_client(
            $uri, $errno, $errstr, $parameters->connection_timeout, $flags
        );
        if (!$resource) {
            $this->onConnectionError(trim($errstr), $errno);
        }
        if (isset($parameters->read_write_timeout)) {
            $rwtimeout = $parameters->read_write_timeout;
            $rwtimeout = $rwtimeout > 0 ? $rwtimeout : -1;
            $timeoutSeconds  = floor($rwtimeout);
            $timeoutUSeconds = ($rwtimeout - $timeoutSeconds) * 1000000;
            stream_set_timeout($resource, $timeoutSeconds, $timeoutUSeconds);
        }
        return $resource;
    }

    private function unixStreamInitializer(IConnectionParameters $parameters) {
        $uri = "unix://{$parameters->path}";
        $flags = STREAM_CLIENT_CONNECT;
        if ($parameters->connection_persistent) {
            $flags |= STREAM_CLIENT_PERSISTENT;
        }
        $resource = @stream_socket_client(
            $uri, $errno, $errstr, $parameters->connection_timeout, $flags
        );
        if (!$resource) {
            $this->onConnectionError(trim($errstr), $errno);
        }
        return $resource;
    }

    public function connect() {
        parent::connect();
        if (count($this->_initCmds) > 0){
            $this->sendInitializationCommands();
        }
    }

    public function disconnect() {
        if ($this->isConnected()) {
            fclose($this->getResource());
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

    protected function writeBytes($buffer) {
        $socket = $this->getResource();
        while (($length = strlen($buffer)) > 0) {
            $written = fwrite($socket, $buffer);
            if ($length === $written) {
                return;
            }
            if ($written === false || $written === 0) {
                $this->onConnectionError('Error while writing bytes to the server');
            }
            $buffer = substr($buffer, $written);
        }
    }

    public function read() {
        $socket = $this->getResource();
        $chunk  = fgets($socket);
        if ($chunk === false || $chunk === '') {
            $this->onConnectionError('Error while reading line from the server');
        }
        $prefix  = $chunk[0];
        $payload = substr($chunk, 1, -2);
        switch ($prefix) {
            case '+':    // inline
                switch ($payload) {
                    case 'OK':
                        return true;
                    case 'QUEUED':
                        return new ResponseQueued();
                    default:
                        return $payload;
                }

            case '$':    // bulk
                $size = (int) $payload;
                if ($size === -1) {
                    return null;
                }
                $bulkData = '';
                $bytesLeft = ($size += 2);
                do {
                    $chunk = fread($socket, min($bytesLeft, 4096));
                    if ($chunk === false || $chunk === '') {
                        $this->onConnectionError(
                            'Error while reading bytes from the server'
                        );
                    }
                    $bulkData .= $chunk;
                    $bytesLeft = $size - strlen($bulkData);
                } while ($bytesLeft > 0);
                return substr($bulkData, 0, -2);

            case '*':    // multi bulk
                $count = (int) $payload;
                if ($count === -1) {
                    return null;
                }
                if ($this->_mbiterable === true) {
                    return new MultiBulkResponseSimple($this, $count);
                }
                $multibulk = array();
                for ($i = 0; $i < $count; $i++) {
                    $multibulk[$i] = $this->read();
                }
                return $multibulk;

            case ':':    // integer
                return (int) $payload;

            case '-':    // error
                if ($this->_throwErrors) {
                    throw new ServerException($payload);
                }
                return new ResponseError($payload);

            default:
                $this->onProtocolError("Unknown prefix: '$prefix'");
        }
    }

    public function writeCommand(ICommand $command) {
        $commandId = $command->getId();
        $arguments = $command->getArguments();

        $cmdlen = strlen($commandId);
        $reqlen = count($arguments) + 1;

        $buffer = "*{$reqlen}\r\n\${$cmdlen}\r\n{$commandId}\r\n";
        for ($i = 0; $i < $reqlen - 1; $i++) {
            $argument = $arguments[$i];
            $arglen  = strlen($argument);
            $buffer .= "\${$arglen}\r\n{$argument}\r\n";
        }
        $this->writeBytes($buffer);
    }
}
