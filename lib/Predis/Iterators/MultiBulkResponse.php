<?php

namespace Predis\Iterators;

abstract class MultiBulkResponse implements \Iterator, \Countable {
    protected $_position, $_current, $_replySize;

    public function rewind() {
        // NOOP
    }

    public function current() {
        return $this->_current;
    }

    public function key() {
        return $this->_position;
    }

    public function next() {
        if (++$this->_position < $this->_replySize) {
            $this->_current = $this->getValue();
        }
        return $this->_position;
    }

    public function valid() {
        return $this->_position < $this->_replySize;
    }

    public function count() {
        // Use count if you want to get the size of the current multi-bulk
        // response without using iterator_count (which actually consumes our
        // iterator to calculate the size, and we cannot perform a rewind)
        return $this->_replySize;
    }

    protected abstract function getValue();
}
