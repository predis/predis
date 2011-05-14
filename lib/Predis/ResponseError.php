<?php

namespace Predis;

class ResponseError {
    public $skipParse = true;
    private $_message;
    private $_type;

    public function __construct($message) {
        $this->_message = $message;
        $this->_type = substr($message, 0, strpos($message, ' '));
    }

    public function __get($property) {
        switch ($property) {
            case 'error':
                return true;
            case 'message':
                return $this->_message;
            case 'type':
                return $this->_type;
        }
    }

    public function __isset($property) {
        return $property === 'error';
    }

    public function __toString() {
        return $this->_message;
    }
}
