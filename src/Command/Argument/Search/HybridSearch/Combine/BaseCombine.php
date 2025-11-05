<?php

namespace Predis\Command\Argument\Search\HybridSearch\Combine;

use Predis\Command\Argument\ArrayableArgument;

abstract class BaseCombine implements ArrayableArgument
{
    /**
     * @var array
     */
    protected $arguments = ['COMBINE'];

    /**
     * @inheritDoc
     */
    abstract public function toArray(): array;
}
