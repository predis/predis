<?php

namespace Predis\Command\Argument\Search\HybridSearch;

use Predis\Command\Argument\ArrayableArgument;

class SearchConfig implements ArrayableArgument
{
    /**
     * @var array
     */
    protected $arguments = ['SEARCH'];

    /**
     * @var ScorerConfig
     */
    protected $scorerConfig;

    public function __construct()
    {
        $this->scorerConfig = new ScorerConfig();
    }

    /**
     * Search query
     *
     * @param string $query
     * @return $this
     */
    public function query(string $query): self
    {
        $this->arguments[] = $query;
        return $this;
    }

    /**
     * @param string $alias
     * @return void
     */
    public function as(string $alias): self
    {
        array_push($this->arguments, 'YIELD_SCORE_AS', $alias);
        return $this;
    }

    /**
     * @param callable(ScorerConfig): void $callable
     * @return self
     */
    public function buildScorerConfig(callable $callable): self
    {
        $callable($this->scorerConfig);
        return $this;
    }

    public function toArray(): array
    {
        $scorerConfig = $this->scorerConfig->toArray();

        if (!empty($scorerConfig)) {
            $this->arguments[] = 'SCORER';
            $this->arguments = array_merge($this->arguments, $scorerConfig);
        }

        return $this->arguments;
    }
}
