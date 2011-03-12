<?php

namespace Predis;

use Predis\Network\IConnectionSingle;

class CommunicationException extends PredisException {
    // Communication errors
    private $_connection;

    public function __construct(IConnectionSingle $connection,
        $message = null, $code = null) {

        $this->_connection = $connection;
        parent::__construct($message, $code);
    }

    public function getConnection() { return $this->_connection; }
    public function shouldResetConnection() {  return true; }
}
