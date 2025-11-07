<?php

namespace Predis\Command\Argument\Search\HybridSearch\VectorSearch;

use Predis\Command\Argument\ArrayableArgument;
use Predis\Command\Redis\Utils\VectorUtility;

abstract class BaseVectorSearchConfig implements ArrayableArgument
{
    public const POLICY_ADHOC = 'ADHOC';
    public const POLICY_BATCHES = 'BATCHES';
    public const POLICY_ACORN = 'ACORN';

    /**
     * @var array
     */
    protected $vector = [];

    /**
     * @var array
     */
    protected $filter = [];

    /**
     * @var array
     */
    protected $as = [];

    /**
     * @var array
     */
    protected $arguments = ['VSIM'];

    /**
     * Vector to perform search against.
     *
     * @param string $field The vector field name to search against. Must start with "@".
     * @param string|float[] $value Binary vector representation or array of floats as vector.
     * @return self
     */
    public function vector(string $field, $value): self
    {
        if (is_array($value)) {
            array_push($this->vector, $field, VectorUtility::toBlob($value));
        } else {
            array_push($this->vector, $field, $value);
        }

        return $this;
    }

    /**
     * @param string $expression
     * @return void
     */
    public function filter(string $expression): self
    {
        array_push($this->filter, 'FILTER', $expression);
        return $this;
    }

    /**
     * @param string $alias
     * @return void
     */
    public function as(string $alias): self
    {
        array_push($this->as, 'YIELD_SCORE_AS', $alias);
        return $this;
    }

    abstract public function toArray(): array;
}
