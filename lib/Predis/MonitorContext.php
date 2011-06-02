<?php

namespace Predis;

class MonitorContext implements \Iterator {
    private $_client;
    private $_isValid;
    private $_position;

    public function __construct(Client $client) {
        $this->checkCapabilities($client);
        $this->_client = $client;
        $this->openContext();
    }

    public function __destruct() {
        $this->closeContext();
    }

    private function checkCapabilities(Client $client) {
        if (Helpers::isCluster($client->getConnection())) {
            throw new ClientException(
                'Cannot initialize a monitor context over a cluster of connections'
            );
        }
        if ($client->getProfile()->supportsCommand('monitor') === false) {
            throw new ClientException(
                'The current profile does not support the MONITOR command'
            );
        }
    }

    protected function openContext() {
        $this->_isValid = true;
        $monitor = $this->_client->createCommand('monitor');
        $this->_client->executeCommand($monitor);
    }

    public function closeContext() {
        $this->_client->disconnect();
        $this->_isValid = false;
    }

    public function rewind() {
        // NOOP
    }

    public function current() {
        return $this->getValue();
    }

    public function key() {
        return $this->_position;
    }

    public function next() {
        $this->_position++;
    }

    public function valid() {
        return $this->_isValid;
    }

    private function getValue() {
        $database = 0;
        $event = $this->_client->getConnection()->read();

        $callback = function($matches) use (&$database) {
            if (isset($matches[1])) {
                $database = (int) $matches[1];
            }
            return ' ';
        };
        $event = preg_replace_callback('/ \(db (\d+)\) /', $callback, $event, 1);

        @list($timestamp, $command, $arguments) = split(' ', $event, 3);
        return (object) array(
            'timestamp' => (float) $timestamp,
            'database'  => $database,
            'command'   => substr($command, 1, -1),
            'arguments' => $arguments,
        );
    }
}
