<?php

/*
 * This file is part of the Predis package.
 *
 * (c) Daniele Alessandri <suppakilla@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Transaction;

use Predis\PredisException;

/**
 * Exception class that identifies a MULTI / EXEC transaction aborted by Redis.
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class AbortedMultiExecException extends PredisException
{
    private $transaction;

    /**
     * @param MultiExec $transaction transaction that generated the exception
     * @param string    $message     error message
     * @param int       $code        error code
     */
    public function __construct(MultiExec $transaction, $message, $code = 0)
    {
        parent::__construct($message, $code);
        $this->transaction = $transaction;
    }

    /**
     * Returns the transaction that generated the exception.
     *
     * @return MultiExec
     */
    public function getTransaction()
    {
        return $this->transaction;
    }
}
