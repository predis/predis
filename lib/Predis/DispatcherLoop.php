<?php

/*
 * This file is part of the Predis package.
 *
 * (c) Daniele Alessandri <suppakilla@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis;

/**
 * Method-dispatcher loop built around the client-side abstraction of a Redis
 * Publish / Subscribe context.
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class DispatcherLoop
{
    private $_client;
    private $_pubSubContext;
    private $_callbacks;
    private $_defaultCallback;
    private $_subscriptionCallback;

    /**
     * @param Client Client instance used by the context.
     */
    public function __construct(Client $client)
    {
        $this->_callbacks = array();
        $this->_client = $client;
        $this->_pubSubContext = $client->pubSub();
    }

    /**
     * Checks if the passed argument is a valid callback.
     *
     * @param mixed A callback.
     */
    protected function validateCallback($callable)
    {
        if (!is_callable($callable)) {
            throw new ClientException("A valid callable object must be provided");
        }
    }

    /**
     * Returns the underlying Publish / Subscribe context.
     *
     * @return PubSubContext
     */
    public function getPubSubContext()
    {
        return $this->_pubSubContext;
    }

    /**
     * Sets a callback that gets invoked upon new subscriptions.
     *
     * @param mixed $callable A callback.
     */
    public function subscriptionCallback($callable = null)
    {
        if (isset($callable)) {
            $this->validateCallback($callable);
        }
        $this->_subscriptionCallback = $callable;
    }

    /**
     * Sets a callback that gets invoked when a message is received on a
     * channel that does not have an associated callback.
     *
     * @param mixed $callable A callback.
     */
    public function defaultCallback($callable = null)
    {
        if (isset($callable)) {
            $this->validateCallback($callable);
        }
        $this->_subscriptionCallback = $callable;
    }

    /**
     * Binds a callback to a channel.
     *
     * @param string $channel Channel name.
     * @param Callable $callback A callback.
     */
    public function attachCallback($channel, $callback)
    {
        $this->validateCallback($callback);
        $this->_callbacks[$channel] = $callback;
        $this->_pubSubContext->subscribe($channel);
    }

    /**
     * Stops listening to a channel and removes the associated callback.
     *
     * @param string $channel Redis channel.
     */
    public function detachCallback($channel)
    {
        if (isset($this->_callbacks[$channel])) {
            unset($this->_callbacks[$channel]);
            $this->_pubSubContext->unsubscribe($channel);
        }
    }

    /**
     * Starts the dispatcher loop.
     */
    public function run()
    {
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

    /**
     * Terminates the dispatcher loop.
     */
    public function stop()
    {
        $this->_pubSubContext->closeContext();
    }
}
