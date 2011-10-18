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

class AbortedMultiExecException extends PredisException
{
    private $_transaction;

    public function __construct(MultiExecContext $transaction, $message, $code = null)
    {
        parent::__construct($message, $code);

        $this->_transaction = $transaction;
    }

    public function getTransaction()
    {
        return $this->_transaction;
    }
}
