<?php

namespace Predis\Commands\Processors;

use Predis\Commands\ICommand;

class ProcessorChain implements ICommandProcessorChain, \ArrayAccess {
    private $_processors;

    public function __construct($processors = array()) {
        foreach ($processors as $processor) {
            $this->add($processor);
        }
    }

    public function add(ICommandProcessor $processor) {
        $this->_processors[] = $processor;
    }

    public function remove(ICommandProcessor $processor) {
        $index = array_search($processor, $this->_processors, true);
        if ($index !== false) {
            unset($this->_processors);
        }
    }

    public function process(ICommand $command) {
        $count = count($this->_processors);
        for ($i = 0; $i < $count; $i++) {
            $this->_processors[$i]->process($command);
        }
    }

    public function getProcessors() {
        return $this->_processors;
    }

    public function getIterator() {
        return new \ArrayIterator($this->_processors);
    }

    public function count() {
        return count($this->_processors);
    }

    public function offsetExists($index) {
        return isset($this->_processors[$index]);
    }

    public function offsetGet($index) {
        return $this->_processors[$index];
    }

    public function offsetSet($index, $processor) {
        if (!$processor instanceof ICommandProcessor) {
            throw new \InvalidArgumentException(
                'A processor chain can hold only instances of classes implementing '.
                'the Predis\Commands\Preprocessors\ICommandProcessor interface'
            );
        }
        $this->_processors[$index] = $processor;
    }

    public function offsetUnset($index) {
        unset($this->_processors[$index]);
    }
}
