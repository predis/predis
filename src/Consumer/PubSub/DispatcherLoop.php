<?php

/*
 * This file is part of the Predis package.
 *
 * (c) 2009-2020 Daniele Alessandri
 * (c) 2021-2025 Till Krüss
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Consumer\PubSub;

use Predis\Command\Processor\KeyPrefixProcessor;
use Predis\Consumer\AbstractDispatcherLoop;

/**
 * Method-dispatcher loop built around the client-side abstraction of a Redis
 * PUB / SUB context.
 */
class DispatcherLoop extends AbstractDispatcherLoop
{
    /**
     * @var Consumer
     */
    protected $consumer;

    public function __construct(Consumer $consumer)
    {
        $this->consumer = $consumer;
    }

    /**
     * Binds a callback to a channel.
     *
     * @param string   $messageType Channel name.
     * @param callable $callback    A callback.
     */
    public function attachCallback(string $messageType, callable $callback): void
    {
        $callbackName = $this->getPrefixKeys() . $messageType;

        $this->callbacksDictionary[$callbackName] = $callback;

        if ($this->consumer->getSubscriptionContext()->getContext() === SubscriptionContext::CONTEXT_SHARDED) {
            $this->consumer->ssubscribe($messageType);
        } else {
            $this->consumer->subscribe($messageType);
        }
    }

    /**
     * Stops listening to a channel and removes the associated callback.
     *
     * @param string $messageType Redis channel.
     */
    public function detachCallback(string $messageType): void
    {
        $callbackName = $this->getPrefixKeys() . $messageType;

        if (isset($this->callbacksDictionary[$callbackName])) {
            unset($this->callbacksDictionary[$callbackName]);

            if ($this->consumer->getSubscriptionContext()->getContext() === SubscriptionContext::CONTEXT_SHARDED) {
                $this->consumer->sunsubscribe($messageType);
            } else {
                $this->consumer->unsubscribe($messageType);
            }
        }
    }

    /**
     * Starts the dispatcher loop.
     */
    public function run(): void
    {
        foreach ($this->consumer as $message) {
            $kind = $message->kind;

            if ($kind !== Consumer::MESSAGE && $kind !== Consumer::PMESSAGE) {
                if (isset($this->defaultCallback)) {
                    $callback = $this->defaultCallback;
                    $callback($message, $this);
                }

                continue;
            }

            if (isset($this->callbacksDictionary[$message->channel])) {
                $callback = $this->callbacksDictionary[$message->channel];
                $callback($message->payload, $this);
            } elseif (isset($this->defaultCallback)) {
                $callback = $this->defaultCallback;
                $callback($message, $this);
            }
        }
    }

    /**
     * Return the prefix used for keys.
     *
     * @return string
     */
    protected function getPrefixKeys(): string
    {
        $options = $this->consumer->getClient()->getOptions();

        if (isset($options->prefix)) {
            /** @var KeyPrefixProcessor $processor */
            $processor = $options->prefix;

            return $processor->getPrefix();
        }

        return '';
    }
}
