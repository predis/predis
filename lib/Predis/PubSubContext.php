<?php

namespace Predis;

class PubSubContext implements \Iterator {
    const SUBSCRIBE    = 'subscribe';
    const UNSUBSCRIBE  = 'unsubscribe';
    const PSUBSCRIBE   = 'psubscribe';
    const PUNSUBSCRIBE = 'punsubscribe';
    const MESSAGE      = 'message';
    const PMESSAGE     = 'pmessage';

    const STATUS_VALID       = 0x0001;
    const STATUS_SUBSCRIBED  = 0x0010;
    const STATUS_PSUBSCRIBED = 0x0100;

    private $_client;
    private $_position;
    private $_options;

    public function __construct(Client $client, Array $options = null) {
        $this->checkCapabilities($client);
        $this->_options = $options ?: array();
        $this->_client  = $client;
        $this->_statusFlags = self::STATUS_VALID;

        $this->genericSubscribeInit('subscribe');
        $this->genericSubscribeInit('psubscribe');
    }

    public function __destruct() {
        $this->closeContext();
    }

    private function checkCapabilities(Client $client) {
        if (Helpers::isCluster($client->getConnection())) {
            throw new ClientException(
                'Cannot initialize a PUB/SUB context over a cluster of connections'
            );
        }
        $commands = array('publish', 'subscribe', 'unsubscribe', 'psubscribe', 'punsubscribe');
        if ($client->getProfile()->supportsCommands($commands) === false) {
            throw new ClientException(
                'The current profile does not support PUB/SUB related commands'
            );
        }
    }

    private function genericSubscribeInit($subscribeAction) {
        if (isset($this->_options[$subscribeAction])) {
            $this->$subscribeAction($this->_options[$subscribeAction]);
        }
    }

    private function isFlagSet($value) {
        return ($this->_statusFlags & $value) === $value;
    }

    public function subscribe(/* arguments */) {
        $this->writeCommand(self::SUBSCRIBE, func_get_args());
        $this->_statusFlags |= self::STATUS_SUBSCRIBED;
    }

    public function unsubscribe(/* arguments */) {
        $this->writeCommand(self::UNSUBSCRIBE, func_get_args());
    }

    public function psubscribe(/* arguments */) {
        $this->writeCommand(self::PSUBSCRIBE, func_get_args());
        $this->_statusFlags |= self::STATUS_PSUBSCRIBED;
    }

    public function punsubscribe(/* arguments */) {
        $this->writeCommand(self::PUNSUBSCRIBE, func_get_args());
    }

    public function closeContext() {
        if ($this->valid()) {
            if ($this->isFlagSet(self::STATUS_SUBSCRIBED)) {
                $this->unsubscribe();
            }
            if ($this->isFlagSet(self::STATUS_PSUBSCRIBED)) {
                $this->punsubscribe();
            }
        }
    }

    private function writeCommand($method, $arguments) {
        $arguments = Helpers::filterArrayArguments($arguments);
        $command = $this->_client->createCommand($method, $arguments);
        $this->_client->getConnection()->writeCommand($command);
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
        if ($this->isFlagSet(self::STATUS_VALID)) {
            $this->_position++;
        }
        return $this->_position;
    }

    public function valid() {
        $isValid = $this->isFlagSet(self::STATUS_VALID);
        $subscriptionFlags = self::STATUS_SUBSCRIBED + self::STATUS_PSUBSCRIBED;
        $hasSubscriptions = ($this->_statusFlags & $subscriptionFlags) > 0;
        return $isValid && $hasSubscriptions;
    }

    private function invalidate() {
        $this->_statusFlags = 0x0000;
    }

    private function getValue() {
        $response = $this->_client->getConnection()->read();
        switch ($response[0]) {
            case self::SUBSCRIBE:
            case self::UNSUBSCRIBE:
            case self::PSUBSCRIBE:
            case self::PUNSUBSCRIBE:
                if ($response[2] === 0) {
                    $this->invalidate();
                }
            case self::MESSAGE:
                return (object) array(
                    'kind'    => $response[0],
                    'channel' => $response[1],
                    'payload' => $response[2],
                );
            case self::PMESSAGE:
                return (object) array(
                    'kind'    => $response[0],
                    'pattern' => $response[1],
                    'channel' => $response[2],
                    'payload' => $response[3],
                );
            default:
                throw new ClientException(
                    "Received an unknown message type {$response[0]} inside of a pubsub context"
                );
        }
    }
}
