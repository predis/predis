<?php

namespace Predis\Options;

class CustomOption implements IOption {
    private $_validate, $_default;

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
            return call_user_func($this->_validate, $value);
        }
    }

    public function getDefault() {
        if ($this->_default !== null) {
            return call_user_func($this->_default);
        }
    }

    public function __invoke($value) {
        return isset($value) ? $this->validate($value) : $this->getDefault();
    }
}
