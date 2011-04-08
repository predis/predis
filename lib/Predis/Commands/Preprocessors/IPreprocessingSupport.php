<?php

namespace Predis\Commands\Preprocessors;

interface IPreprocessingSupport {
    public function setPreprocessor(ICommandPreprocessor $processor);
    public function getPreprocessor();
}
