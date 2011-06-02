<?php

namespace Predis;

use Predis\Network\IConnectionSingle;

abstract class CommunicationException extends PredisException {
    private $_connection;

    public function __construct(IConnectionSingle $connection, $message = null,
        $code = null, \Exception $innerException = null) {

        $this->_connection = $connection;
        parent::__construct($message, $code, $innerException);
    }

    public function getConnection() {
        return $this->_connection;
    }

    public function shouldResetConnection() {
        return true;
    }
}
