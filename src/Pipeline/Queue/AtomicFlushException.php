<?php

/*
 * This file is part of the Predis package.
 *
 * (c) Daniele Alessandri <suppakilla@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Pipeline\Queue;

use Predis\Connection\ConnectionInterface;
use Throwable;

/**
 * Exception class for command queue errors during flush operations.
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class AtomicFlushException extends FlushException
{
    const TX_STATE = 0b001;
    const TX_LOGIC = 0b010;
    const TX_ABORT = 0b100;

    /**
     * Returns whether the exception code matches a transaction logic error.
     *
     * @return bool
     */
    public function isStateError(): bool
    {
        return $this->getCode() === self::TX_STATE;
    }

    /**
     * Returns whether the exception code matches a transaction logic error.
     *
     * @return bool
     */
    public function isLogicError(): bool
    {
        return $this->getCode() === self::TX_LOGIC;
    }

    /**
     * Returns whether the exception code matches an aborted transaction.
     *
     * @return bool
     */
    public function isAbortedError(): bool
    {
        return $this->getCode() === self::TX_ABORT;
    }
}
