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
     * Search query.
     *
     * @param  string $query
     * @return $this
     */
    public function query(string $query): self
    {
        $this->arguments[] = $query;

        return $this;
    }

    /**
     * @param  string $alias
     * @return $this
     */
    public function as(string $alias): self
    {
        array_push($this->arguments, 'YIELD_SCORE_AS', $alias);

        return $this;
    }

    /**
     * @param  callable(ScorerConfig): void $callable
     * @return $this
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
