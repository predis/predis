<?php

namespace Predis\Transaction;

use Predis\PredisException;

class AbortedMultiExecException extends PredisException {
    private $_transaction;

    public function __construct(MultiExecContext $transaction, $message, $code = null) {
        $this->_transaction = $transaction;
        parent::__construct($message, $code);
    }

    public function getTransaction() {
        return $this->_transaction;
    }
}
