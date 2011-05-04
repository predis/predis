<?php

namespace Predis\Commands\Processors;

interface IProcessingSupport {
    public function setProcessor(ICommandProcessor $processor);
    public function getProcessor();
}
