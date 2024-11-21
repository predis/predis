<?php

/*
 * This file is part of the Predis package.
 *
 * (c) 2009-2020 Daniele Alessandri
 * (c) 2021-2024 Till Krüss
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\PubSub;

use Iterator;
use ReturnTypeWillChange;

/**
 * Base implementation of a PUB/SUB consumer abstraction based on PHP iterators.
 */
abstract class AbstractConsumer implements Iterator
{
    public const SUBSCRIBE = 'subscribe';
    public const UNSUBSCRIBE = 'unsubscribe';
    public const PSUBSCRIBE = 'psubscribe';
    public const PUNSUBSCRIBE = 'punsubscribe';
    public const MESSAGE = 'message';
    public const PMESSAGE = 'pmessage';
    public const PONG = 'pong';

    public const STATUS_VALID = 1;       // 0b0001
    public const STATUS_SUBSCRIBED = 2;  // 0b0010
    public const STATUS_PSUBSCRIBED = 4; // 0b0100

    protected $position;
    protected $statusFlags = self::STATUS_VALID;

    /**
     * Automatically stops the consumer when the garbage collector kicks in.
     */
    public function __destruct()
    {
        $this->stop(true);
    }

    /**
     * Checks if the specified flag is valid based on the state of the consumer.
     *
     * @param int $value Flag.
     *
     * @return bool
     */
    protected function isFlagSet($value)
    {
        return ($this->statusFlags & $value) === $value;
    }

    /**
     * Subscribes to the specified channels.
     *
     * @param string ...$channel One or more channel names.
     */
    public function subscribe($channel /* , ... */)
    {
        $this->writeRequest(self::SUBSCRIBE, func_get_args());
        $this->statusFlags |= self::STATUS_SUBSCRIBED;
    }

    /**
     * Unsubscribes from the specified channels.
     *
     * @param string ...$channel One or more channel names.
     */
    public function unsubscribe(...$channel)
    {
        $this->writeRequest(self::UNSUBSCRIBE, func_get_args());
    }

    /**
     * Subscribes to the specified channels using a pattern.
     *
     * @param string ...$pattern One or more channel name patterns.
     */
    public function psubscribe(...$pattern)
    {
        $this->writeRequest(self::PSUBSCRIBE, func_get_args());
        $this->statusFlags |= self::STATUS_PSUBSCRIBED;
    }

    /**
     * Unsubscribes from the specified channels using a pattern.
     *
     * @param string ...$pattern One or more channel name patterns.
     */
    public function punsubscribe(...$pattern)
    {
        $this->writeRequest(self::PUNSUBSCRIBE, func_get_args());
    }

    /**
     * PING the server with an optional payload that will be echoed as a
     * PONG message in the pub/sub loop.
     *
     * @param string $payload Optional PING payload.
     */
    public function ping($payload = null)
    {
        $this->writeRequest('PING', [$payload]);
    }

    /**
     * Closes the context by unsubscribing from all the subscribed channels. The
     * context can be forcefully closed by dropping the underlying connection.
     *
     * @param bool $drop Indicates if the context should be closed by dropping the connection.
     *
     * @return bool Returns false when there are no pending messages.
     */
    public function stop($drop = false)
    {
        if (!$this->valid()) {
            return false;
        }

        if ($drop) {
            $this->invalidate();
            $this->disconnect();
        } else {
            if ($this->isFlagSet(self::STATUS_SUBSCRIBED)) {
                $this->unsubscribe();
            }
            if ($this->isFlagSet(self::STATUS_PSUBSCRIBED)) {
                $this->punsubscribe();
            }
        }

        return !$drop;
    }

    /**
     * Closes the underlying connection when forcing a disconnection.
     */
    abstract protected function disconnect();

    /**
     * Writes a Redis command on the underlying connection.
     *
     * @param string $method    Command ID.
     * @param array  $arguments Arguments for the command.
     */
    abstract protected function writeRequest($method, $arguments);

    /**
     * @return void
     */
    #[ReturnTypeWillChange]
    public function rewind()
    {
        // NOOP
    }

    /**
     * Returns the last message payload retrieved from the server and generated
     * by one of the active subscriptions.
     *
     * @return array
     */
    #[ReturnTypeWillChange]
    public function current()
    {
        return $this->getValue();
    }

    /**
     * @return int|null
     */
    #[ReturnTypeWillChange]
    public function key()
    {
        return $this->position;
    }

    /**
     * @return int|null
     */
    #[ReturnTypeWillChange]
    public function next()
    {
        if ($this->valid()) {
            ++$this->position;
        }

        return $this->position;
    }

    /**
     * Checks if the the consumer is still in a valid state to continue.
     *
     * @return bool
     */
    #[ReturnTypeWillChange]
    public function valid()
    {
        $isValid = $this->isFlagSet(self::STATUS_VALID);
        $subscriptionFlags = self::STATUS_SUBSCRIBED | self::STATUS_PSUBSCRIBED;
        $hasSubscriptions = ($this->statusFlags & $subscriptionFlags) > 0;

        return $isValid && $hasSubscriptions;
    }

    /**
     * Resets the state of the consumer.
     */
    protected function invalidate()
    {
        $this->statusFlags = 0;    // 0b0000;
    }

    /**
     * Waits for a new message from the server generated by one of the active
     * subscriptions and returns it when available.
     *
     * @return array
     */
    abstract protected function getValue();
}
