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

namespace Predis\Connection;

trait RelayMethods
{
    /**
     * Registers a new `flushed` event listener.
     *
     * @param  callable $callback
     * @return bool
     */
    public function onFlushed(?callable $callback)
    {
        return $this->client->onFlushed($callback);
    }

    /**
     * Registers a new `invalidated` event listener.
     *
     * @param  callable    $callback
     * @param  string|null $pattern
     * @return bool
     */
    public function onInvalidated(?callable $callback, ?string $pattern = null)
    {
        return $this->client->onInvalidated($callback, $pattern);
    }

    /**
     * Dispatches all pending events.
     *
     * @return int|false
     */
    public function dispatchEvents()
    {
        return $this->client->dispatchEvents();
    }

    /**
     * Adds ignore pattern(s). Matching keys will not be cached in memory.
     *
     * @param  string $pattern,...
     * @return int
     */
    public function addIgnorePatterns(string ...$pattern)
    {
        return $this->client->addIgnorePatterns(...$pattern);
    }

    /**
     * Adds allow pattern(s). Only matching keys will be cached in memory.
     *
     * @param  string $pattern,...
     * @return int
     */
    public function addAllowPatterns(string ...$pattern)
    {
        return $this->client->addAllowPatterns(...$pattern);
    }

    /**
     * Returns the connection's endpoint identifier.
     *
     * @return string|false
     */
    public function endpointId()
    {
        return $this->client->endpointId();
    }

    /**
     * Returns a unique representation of the underlying socket connection identifier.
     *
     * @return string|false
     */
    public function socketId()
    {
        return $this->client->socketId();
    }

    /**
     * Returns information about the license.
     *
     * @return array<string, mixed>
     */
    public function license()
    {
        return $this->client->license();
    }

    /**
     * Returns statistics about Relay.
     *
     * @return array<string, array<string, mixed>>
     */
    public function stats()
    {
        return $this->client->stats();
    }

    /**
     * Returns the number of bytes allocated, or `0` in client-only mode.
     *
     * @return int
     */
    public function maxMemory()
    {
        return $this->client->maxMemory();
    }

    /**
     * Flushes Relay's in-memory cache of all databases.
     * When given an endpoint, only that connection will be flushed.
     * When given an endpoint and database index, only that database
     * for that connection will be flushed.
     *
     * @param  ?string $endpointId
     * @param  ?int    $db
     * @return bool
     */
    public function flushMemory(?string $endpointId = null, ?int $db = null)
    {
        return $this->client->flushMemory($endpointId, $db);
    }
}
