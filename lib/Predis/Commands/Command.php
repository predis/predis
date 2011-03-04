<?php

namespace Predis\Commands;

use Predis\Distribution\IDistributionStrategy;

abstract class Command implements ICommand {
    private $_hash;
    private $_arguments = array();

    public function canBeHashed() {
        return true;
    }

    public function getHash(IDistributionStrategy $distributor) {
        if (isset($this->_hash)) {
            return $this->_hash;
        }
        if (isset($this->_arguments[0])) {
            // TODO: should we throw an exception if the command does not
            // support sharding?
            $key = $this->_arguments[0];

            $start = strpos($key, '{');
            if ($start !== false) {
                $end = strpos($key, '}', $start);
                if ($end !== false) {
                    $key = substr($key, ++$start, $end - $start);
                }
            }

            $this->_hash = $distributor->generateKey($key);
            return $this->_hash;
        }
        return null;
    }

    protected function filterArguments(Array $arguments) {
        return $arguments;
    }

    public function setArguments(/* arguments */) {
        $this->_arguments = $this->filterArguments(func_get_args());
        unset($this->_hash);
    }

    public function setArgumentsArray(Array $arguments) {
        $this->_arguments = $this->filterArguments($arguments);
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

    public function parseResponse($data) {
        return $data;
    }

    public function __toString() {
        $reducer = function($acc, $arg) {
            if (strlen($arg) > 32) {
                $arg = substr($arg, 0, 32) . '[...]';
            }
            $acc .= " $arg";
            return $acc;
        };
        return array_reduce($this->getArguments(), $reducer, $this->getId());
    }
}
