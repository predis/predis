<?php

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
