<?php

namespace Predis;

class ServerException extends PredisException {
    private $_errorType;

    public function __construct($message) {
        parent::__construct($message);
        $this->_errorType = substr($message, 0, strpos($message, ' '));
    }

    public function toResponseError() {
        return new ResponseError($this->getMessage());
    }

    public function getErrorType() {
        return $this->_errorType;
    }
}
