<?php

namespace Predis\Commands;

use Predis\Helpers;
use Predis\Distribution\INodeKeyGenerator;

abstract class Command implements ICommand {
    private $_hash;
    private $_arguments = array();

    protected function filterArguments(Array $arguments) {
        return $arguments;
    }

    public function setArguments(Array $arguments) {
        $this->_arguments = $this->filterArguments($arguments);
        unset($this->_hash);
    }

    public function setRawArguments(Array $arguments) {
        $this->_arguments = $arguments;
        unset($this->_hash);
    }

    public function getArguments() {
        return $this->_arguments;
    }

    public function getArgument($index = 0) {
        if (isset($this->_arguments[$index]) === true) {
            return $this->_arguments[$index];
        }
    }

    protected function onPrefixKeys(Array $arguments, $prefix) {
        $arguments[0] = "$prefix{$arguments[0]}";
        return $arguments;
    }

    public function prefixKeys($prefix) {
        $arguments = $this->onPrefixKeys($this->_arguments, $prefix);
        if (isset($arguments)) {
            $this->_arguments = $arguments;
            unset($this->_hash);
        }
    }

    protected function canBeHashed() {
        return isset($this->_arguments[0]);
    }

    protected function checkSameHashForKeys(Array $keys) {
        if (($count = count($keys)) === 0) {
            return false;
        }
        $currentKey = Helpers::getKeyHashablePart($keys[0]);
        for ($i = 1; $i < $count; $i++) {
            $nextKey = Helpers::getKeyHashablePart($keys[$i]);
            if ($currentKey !== $nextKey) {
                return false;
            }
            $currentKey = $nextKey;
        }
        return true;
    }

    public function getHash(INodeKeyGenerator $distributor) {
        if (isset($this->_hash)) {
            return $this->_hash;
        }
        if ($this->canBeHashed()) {
            $key = Helpers::getKeyHashablePart($this->_arguments[0]);
            $this->_hash = $distributor->generateKey($key);
            return $this->_hash;
        }
        return null;
    }

    public function parseResponse($data) {
        return $data;
    }

    protected function toStringArgumentReducer($accumulator, $argument) {
        if (strlen($argument) > 32) {
            $argument = substr($argument, 0, 32) . '[...]';
        }
        $accumulator .= " $argument";
        return $accumulator;
    }

    public function __toString() {
        return array_reduce(
            $this->getArguments(),
            array($this, 'toStringArgumentReducer'),
            $this->getId()
        );
    }
}
