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

namespace Predis\Consumer;

/**
 * Abstraction around consumer interface to invoke callbacks on received messages.
 */
interface DispatcherLoopInterface
{
    /**
     * Returns consumer interface instance.
     *
     * @return ConsumerInterface
     */
    public function getConsumer(): ConsumerInterface;

    /**
     * Sets default callback that invokes if message type have no matching callback.
     *
     * @param  callable|null $callback
     * @return void
     */
    public function setDefaultCallback(?callable $callback = null): void;

    /**
     * Binds given message type to given callback.
     *
     * @param  string   $messageType
     * @param  callable $callback
     * @return void
     */
    public function attachCallback(string $messageType, callable $callback): void;

    /**
     * Removes connection between given message type and previously assigned callback.
     *
     * @param  string $messageType
     * @return void
     */
    public function detachCallback(string $messageType): void;

    /**
     * Starts consumer loop.
     *
     * @return void
     */
    public function run(): void;

    /**
     * Stops consumer loop.
     *
     * @return void
     */
    public function stop(): void;
}
