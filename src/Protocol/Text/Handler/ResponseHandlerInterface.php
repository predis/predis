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

namespace Predis\Protocol\Text\Handler;

use Predis\Connection\CompositeConnectionInterface;

/**
 * Defines a pluggable handler used to parse a particular type of response.
 */
interface ResponseHandlerInterface
{
    /**
     * Deserializes a response returned by Redis and reads more data from the
     * connection if needed.
     *
     * @param CompositeConnectionInterface $connection Redis connection.
     * @param string                       $payload    String payload.
     *
     * @return mixed
     */
    public function handle(CompositeConnectionInterface $connection, $payload);
}
