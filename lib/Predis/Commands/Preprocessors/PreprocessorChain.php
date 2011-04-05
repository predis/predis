<?php

namespace Predis\Commands\Preprocessors;

class PreprocessorChain implements ICommandPreprocessorChain, \ArrayAccess {
    private $_preprocessors;

    public function __construct($preprocessors = array()) {
        foreach ($preprocessors as $preprocessor) {
            $this->add($preprocessor);
        }
    }

    public function add(ICommandPreprocessor $preprocessor) {
        $this->_preprocessors[] = $preprocessor;
    }

    public function remove(ICommandPreprocessor $preprocessor) {
        $index = array_search($preprocessor, $this->_preprocessors, true);
        if ($index !== false) {
            unset($this->_preprocessors);
        }
    }

    public function process(&$method, &$arguments) {
        $count = count($this->_preprocessors);
        for ($i = 0; $i < $count; $i++) {
            $this->_preprocessors[$i]->process($method, $arguments);
        }
    }

    public function getPreprocessors() {
        return $this->_preprocessors;
    }

    public function getIterator() {
        return new \ArrayIterator($this->_preprocessors);
    }

    public function count() {
        return count($this->_preprocessors);
    }

    public function offsetExists($index) {
        return isset($this->_preprocessors[$index]);
    }

    public function offsetGet($index) {
        return $this->_preprocessors[$index];
    }

    public function offsetSet($index, $preprocessor) {
        if (!$preprocessor instanceof ICommandPreprocessor) {
            throw new \InvalidArgumentException(
                'A preprocessor chain can hold only instances of classes implementing '.
                'the Predis\Commands\Preprocessors\ICommandPreprocessor interface'
            );
        }
        $this->_preprocessors[$index] = $preprocessor;
    }

    public function offsetUnset($index) {
        unset($this->_preprocessors[$index]);
    }
}
