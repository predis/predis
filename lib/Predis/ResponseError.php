<?php

namespace Predis;

class ResponseError implements IRedisServerError {
    private $_message;

    public function __construct($message) {
        $this->_message = $message;
    }

    public function getMessage() {
        return $this->_message;
    }

    public function getErrorType() {
        list($errorType, ) = explode(' ', $this->getMessage(), 2);
        return $errorType;
    }

    public function __toString() {
        return $this->getMessage();
    }
}
