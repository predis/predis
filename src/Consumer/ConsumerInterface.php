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

use Iterator;
use Predis\ClientInterface;
use ReturnTypeWillChange;

interface ConsumerInterface extends Iterator
{
    /**
     * @param ClientInterface $client
     */
    public function __construct(ClientInterface $client);

    /**
     * Stops consumer loop, with optional client disconnection.
     *
     * @param  bool $drop
     * @return bool
     */
    public function stop(bool $drop = false): bool;

    /**
     * Returns consumer client instance.
     *
     * @return ClientInterface
     */
    public function getClient(): ClientInterface;

    /**
     * Returns last payload produced by server.
     *
     * @return mixed
     */
    #[ReturnTypeWillChange]
    public function current();

    /**
     * Keeps loop until consumer is in valid state.
     *
     * @return void
     */
    #[ReturnTypeWillChange]
    public function next();

    /**
     * Checks if consumer is in the valid state to continue.
     *
     * @return bool
     */
    #[ReturnTypeWillChange]
    public function valid();
}
