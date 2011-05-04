<?php

namespace Predis\Commands\Processors;

interface ICommandProcessor {
    public function process($method, &$arguments);
}
