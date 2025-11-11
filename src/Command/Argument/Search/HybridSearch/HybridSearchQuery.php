<?php

/*
 * This file is part of the Predis package.
 *
 * (c) 2009-2020 Daniele Alessandri
 * (c) 2021-2025 Till Krüss
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Command\Argument\Search\HybridSearch;

use Predis\Command\Argument\ArrayableArgument;
use Predis\Command\Argument\Search\HybridSearch\Combine\LinearCombineConfig;
use Predis\Command\Argument\Search\HybridSearch\Combine\RRFCombineConfig;
use Predis\Command\Argument\Search\HybridSearch\VectorSearch\KNNVectorSearchConfig;
use Predis\Command\Argument\Search\HybridSearch\VectorSearch\RangeVectorSearchConfig;
use Predis\Command\Redis\Utils\CommandUtility;
use ValueError;

class HybridSearchQuery implements ArrayableArgument
{
    public const SORT_ASC = 'ASC';
    public const SORT_DESC = 'DESC';

    /**
     * @var SearchConfig
     */
    protected $searchConfig;

    /**
     * The vector search portion of the query.
     *
     * @var KNNVectorSearchConfig|RangeVectorSearchConfig
     */
    protected $vectorSearchConfig;

    /**
     * Configuration for the score fusion method (optional).
     * If not provided, Reciprocal Rank Fusion (RRF) is used with server-side default parameters.
     *
     * @var RRFCombineConfig|LinearCombineConfig
     */
    protected $combineConfig;

    /**
     * @var array
     */
    protected $load = [];

    /**
     * @var array
     */
    protected $groupBy = [];

    /**
     * @var array
     */
    protected $apply = [];

    /**
     * @var array
     */
    protected $sortBy = [];

    /**
     * @var string
     */
    protected $filter;

    /**
     * @var array
     */
    protected $limit = [];

    /**
     * @var array
     */
    protected $params = [];

    /**
     * @var bool
     */
    protected $explainScore = false;

    /**
     * @var bool
     */
    protected $timeout = false;

    /**
     * @var array
     */
    protected $withCursor = [];

    /**
     * @var array
     */
    protected $arguments = [];

    /**
     * @param string $vectorSearchMethod Class type of desired vector search method
     * @param string $combineMethod      Class type of desired combine method
     */
    public function __construct(
        string $vectorSearchMethod = KNNVectorSearchConfig::class,
        string $combineMethod = RRFCombineConfig::class
    ) {
        $this->searchConfig = new SearchConfig();
        $this->vectorSearchConfig = new $vectorSearchMethod();
        $this->combineConfig = new $combineMethod();
    }

    /**
     * @param  callable(SearchConfig): void $callable
     * @return $this
     */
    public function buildSearchConfig(callable $callable): self
    {
        $callable($this->searchConfig);

        return $this;
    }

    /**
     * @param  callable(KNNVectorSearchConfig|RangeVectorSearchConfig): void $callable
     * @return $this
     */
    public function buildVectorSearchConfig(callable $callable): self
    {
        $callable($this->vectorSearchConfig);

        return $this;
    }

    /**
     * @param  callable(RRFCombineConfig|LinearCombineConfig): void $callable
     * @return $this
     */
    public function buildCombineConfig(callable $callable): self
    {
        $callable($this->combineConfig);

        return $this;
    }

    /**
     * The list of fields to return in the results.
     *
     * @param  array $fields
     * @return $this
     */
    public function load(array $fields): self
    {
        array_push($this->load, 'LOAD', count($fields), ...$fields);

        return $this;
    }

    /**
     * @param  array     $fields
     * @param  Reducer[] $reducers
     * @return $this
     */
    public function groupBy(array $fields, array $reducers): self
    {
        array_push($this->groupBy, 'GROUPBY', count($fields), ...$fields);

        foreach ($reducers as $reducer) {
            array_push($this->groupBy, 'REDUCE', ...$reducer->toArray());
        }

        return $this;
    }

    /**
     * @param  array $expressionFieldDict field => function dictionary
     * @return $this
     */
    public function apply(array $expressionFieldDict): self
    {
        foreach ($expressionFieldDict as $field => $function) {
            array_push($this->apply, 'APPLY', $function, 'AS', $field);
        }

        return $this;
    }

    /**
     * Sorts the final results by a specific field.
     *
     * @param  array<string, string> $fields Dictionary with fields and sort direction. Check class constants.
     * @return $this
     */
    public function sortBy(array $fields): self
    {
        $fieldsArray = [];
        foreach ($fields as $field => $direction) {
            if (!in_array(strtoupper($direction), [self::SORT_ASC, self::SORT_DESC])) {
                throw new ValueError('Sort direction must be one of "ASC" or "DESC".');
            }

            array_push($fieldsArray, $field, $direction);
        }

        array_push($this->sortBy, 'SORTBY', count($fieldsArray), ...$fieldsArray);

        return $this;
    }

    /**
     * Final result filtering.
     *
     * @param  string $expression
     * @return $this
     */
    public function filter(string $expression): self
    {
        $this->filter = $expression;

        return $this;
    }

    /**
     * @param  int   $offset
     * @param  int   $num
     * @return $this
     */
    public function limit(int $offset, int $num): self
    {
        array_push($this->limit, 'LIMIT', $offset, $num);

        return $this;
    }

    /**
     * Binds values to named parameters in the query string.
     *
     * @param  array $params
     * @return $this
     */
    public function params(array $params): self
    {
        $arrayParams = CommandUtility::dictionaryToArray($params);
        array_push($this->params, 'PARAMS', count($arrayParams), ...$arrayParams);

        return $this;
    }

    /**
     * @return $this
     */
    public function explainScore(): self
    {
        $this->explainScore = true;

        return $this;
    }

    /**
     * @return $this
     */
    public function timeout(): self
    {
        $this->timeout = true;

        return $this;
    }

    /**
     * @param  int|null $readSize
     * @param  int|null $idleTime
     * @return $this
     */
    public function withCursor(?int $readSize = null, ?int $idleTime = null): self
    {
        $this->withCursor[] = 'WITHCURSOR';

        if ($readSize) {
            array_push($this->withCursor, 'COUNT', $readSize);
        }

        if ($idleTime) {
            array_push($this->withCursor, 'MAXIDLE', $idleTime);
        }

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function toArray(): array
    {
        $this->arguments = array_merge(
            $this->arguments,
            $this->searchConfig->toArray(),
            $this->vectorSearchConfig->toArray()
        );

        $combineConfig = $this->combineConfig->toArray();

        // Only add if any configuration was applied
        if (count($combineConfig) > 2) {
            $this->arguments = array_merge($this->arguments, $combineConfig);
        }

        if ($this->load) {
            $this->arguments = array_merge($this->arguments, $this->load);
        }

        if ($this->groupBy) {
            $this->arguments = array_merge($this->arguments, $this->groupBy);
        }

        if ($this->apply) {
            $this->arguments = array_merge($this->arguments, $this->apply);
        }

        if ($this->sortBy) {
            $this->arguments = array_merge($this->arguments, $this->sortBy);
        }

        if ($this->filter) {
            array_push($this->arguments, 'FILTER', $this->filter);
        }

        if ($this->limit) {
            $this->arguments = array_merge($this->arguments, $this->limit);
        }

        if ($this->params) {
            $this->arguments = array_merge($this->arguments, $this->params);
        }

        if ($this->explainScore) {
            $this->arguments[] = 'EXPLAINSCORE';
        }

        if ($this->timeout) {
            $this->arguments[] = 'TIMEOUT';
        }

        if ($this->withCursor) {
            $this->arguments = array_merge($this->arguments, $this->withCursor);
        }

        return $this->arguments;
    }
}
