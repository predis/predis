<?php

namespace Predis\Options;

class CustomOption implements IOption {
    private $_validate;
    private $_default;

    public function __construct(Array $options) {
        $this->_validate = $this->filterCallable($options, 'validate');
        $this->_default  = $this->filterCallable($options, 'default');
    }

    private function filterCallable($options, $key) {
        if (!isset($options[$key])) {
            return;
        }
        $callable = $options[$key];
        if (is_callable($callable)) {
            return $callable;
        }
        throw new \InvalidArgumentException("The parameter $key must be callable");
    }

    public function validate($value) {
        if (isset($value)) {
            if ($this->_validate === null) {
                return $value;
            }
            $validator = $this->_validate;
            return $validator($value);
        }
    }

    public function getDefault() {
        if (!isset($this->_default)) {
            return;
        }
        $default = $this->_default;
        return $default();
    }

    public function __invoke($value) {
        if (isset($value)) {
            return $this->validate($value);
        }
        return $this->getDefault();
    }
}
