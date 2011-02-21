<?php

namespace Predis\Network;

use Predis\ConnectionParameters;
use Predis\CommunicationException;

class UnixDomainSocketConnection extends TcpConnection {
    protected function checkParameters(ConnectionParameters $parameters) {
        if ($parameters->scheme != 'unix') {
            throw new \InvalidArgumentException("Invalid scheme: {$parameters->scheme}");
        }
        $pathToSocket = $parameters->path;
        if (!isset($pathToSocket)) {
            throw new \InvalidArgumentException('Missing UNIX domain socket path');
        }
        if (!file_exists($pathToSocket)) {
            throw new \InvalidArgumentException("Could not find $pathToSocket");
        }
        return $parameters;
    }

    protected function createResource() {
        $uri = sprintf('unix:///%s', $this->_params->path);
        $connectFlags = STREAM_CLIENT_CONNECT;
        if ($this->_params->connection_persistent) {
            $connectFlags |= STREAM_CLIENT_PERSISTENT;
        }
        $this->_socket = @stream_socket_client(
            $uri, $errno, $errstr, $this->_params->connection_timeout, $connectFlags
        );
        if (!$this->_socket) {
            $this->onCommunicationException(trim($errstr), $errno);
        }
    }
}
