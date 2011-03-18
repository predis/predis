<?php

namespace Predis\Options;

class Option implements IOption {
    public function validate($value) {
        return $value;
    }

    public function getDefault() {
        return null;
    }

    public function __invoke($value) {
        return isset($value) ? $this->validate($value) : $this->getDefault();
    }
}
