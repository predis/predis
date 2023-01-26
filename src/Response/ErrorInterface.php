<?php

/*
 * This file is part of the Predis package.
 *
 * (c) 2009-2020 Daniele Alessandri
 * (c) 2021-2023 Till Krüss
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Response;

/**
 * Represents an error returned by Redis (responses identified by "-" in the
 * Redis protocol) during the execution of an operation on the server.
 */
interface ErrorInterface extends ResponseInterface
{
    /**
     * Returns the error message.
     *
     * @return string
     */
    public function getMessage();

    /**
     * Returns the error type (e.g. ERR, ASK, MOVED).
     *
     * @return string
     */
    public function getErrorType();
}
