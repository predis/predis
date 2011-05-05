<?php

namespace Predis\Commands\Processors;

interface ICommandProcessorChain
    extends ICommandProcessor, \IteratorAggregate, \Countable {

    public function add(ICommandProcessor $preprocessor);
    public function remove(ICommandProcessor $preprocessor);
    public function getProcessors();
}
