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
     * @var array
     */
    protected $as = [];

    /**
     * @param string $alias
     * @return void
     */
    public function as(string $alias): self
    {
        array_push($this->as, 'YIELD_SCORE_AS', $alias);
        return $this;
    }

    /**
     * @inheritDoc
     */
    abstract public function toArray(): array;
}
