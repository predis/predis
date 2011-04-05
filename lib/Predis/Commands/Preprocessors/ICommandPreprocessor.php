<?php

namespace Predis\Commands\Preprocessors;

interface ICommandPreprocessor {
    public function process(&$method, &$arguments);
}
