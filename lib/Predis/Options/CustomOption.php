<?php

namespace Predis\Options;

class CustomOption extends Option {
    private $_validate, $_default;

    public function __construct(Array $options) {
        $validate = isset($options['validate']) ? $options['validate'] : 'parent::validate';
        $default  = isset($options['default']) ? $options['default'] : 'parent::getDefault';
        if (!is_callable($validate) || !is_callable($default)) {
            throw new \InvalidArgumentException("Validate and default must be callable");
        }
        $this->_validate = $validate;
        $this->_default  = $default;
    }

    public function validate($value) {
        if (isset($value)) {
            return call_user_func($this->_validate, $value);
        }
    }

    public function getDefault() {
        return $this->validate(call_user_func($this->_default));
    }
}
