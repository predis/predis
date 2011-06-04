<?php

namespace Predis;

class DispatcherLoop {
    private $_client;
    private $_pubSubContext;
    private $_callbacks;
    private $_defaultCallback;
    private $_subscriptionCallback;

    public function __construct(Client $client) {
        $this->_callbacks = array();
        $this->_client = $client;
        $this->_pubSubContext = $client->pubSub();
    }

    protected function validateCallback($callback) {
        if (!is_callable($callback)) {
            throw new ClientException(
                "The callback parameter must be a valid callable object"
            );
        }
    }

    public function getPubSubContext() {
        return $this->_pubSubContext;
    }

    public function subscriptionCallback($callback = null) {
        if (isset($callback)) {
            $this->validateCallback($callback);
        }
        $this->_subscriptionCallback = $callback;
    }

    public function defaultCallback($callback = null) {
        if (isset($callback)) {
            $this->validateCallback($callback);
        }
        $this->_subscriptionCallback = $callback;
    }

    public function attachCallback($channel, $callback) {
        $this->validateCallback($callback);
        $this->_callbacks[$channel] = $callback;
        $this->_pubSubContext->subscribe($channel);
    }

    public function detachCallback($channel) {
        if (isset($this->_callbacks[$channel])) {
            unset($this->_callbacks[$channel]);
            $this->_pubSubContext->unsubscribe($channel);
        }
    }

    public function run() {
        foreach ($this->_pubSubContext as $message) {
            $kind = $message->kind;
            if ($kind !== PubSubContext::MESSAGE && $kind !== PubSubContext::PMESSAGE) {
                if (isset($this->_subscriptionCallback)) {
                    $callback = $this->_subscriptionCallback;
                    $callback($message);
                }
                continue;
            }
            if (isset($this->_callbacks[$message->channel])) {
                $callback = $this->_callbacks[$message->channel];
                $callback($message->payload);
            }
            else if (isset($this->_defaultCallback)) {
                $callback = $this->_defaultCallback;
                $callback($message);
            }
        }
    }

    public function stop() {
        $this->_pubSubContext->closeContext();
    }
}
