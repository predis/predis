<?php

namespace Predis\Commands\Preprocessors;

interface ICommandPreprocessorChain
    extends ICommandPreprocessor, \IteratorAggregate, \Countable {

    public function add(ICommandPreprocessor $preprocessor);
    public function remove(ICommandPreprocessor $preprocessor);
    public function getPreprocessors();
}
