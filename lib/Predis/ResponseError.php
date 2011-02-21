<?php

namespace Predis;

class ResponseError {
    public $skipParse = true;
    private $_message;

    public function __construct($message) {
        $this->_message = $message;
    }

    public function __get($property) {
        if ($property === 'error') {
            return true;
        }
        if ($property === 'message') {
            return $this->_message;
        }
    }

    public function __isset($property) {
        return $property === 'error';
    }

    public function __toString() {
        return $this->_message;
    }
}
