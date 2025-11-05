<?php

namespace Predis\Command\Argument\Search\HybridSearch;

use Predis\Command\Argument\ArrayableArgument;

class ScorerConfig implements ArrayableArgument
{
    const TYPE_BM25 = 'BM25';
    const TYPE_TFIDF = 'TFIDF';
    const TYPE_DISMAX = 'DISMAX';
    const TYPE_DOCSCORE = 'DOCSCORE';

    /**
     * @var array
     */
    protected $arguments = [];

    /**
     * The text scoring algorithm. Defaults to BM25.
     *
     * @param string $type
     * @return void
     */
    public function type(string $type = self::TYPE_BM25): self
    {
        $this->arguments[] = $type;
        return $this;
    }

    /**
     * An alias for the text score field in the results.
     * The aliased field will be included in the `value` object of each returned document.
     *
     * @param string $alias
     * @return void
     */
    public function as(string $alias): self
    {
        array_push($this->arguments, 'YIELD_SCORE_AS', $alias);

        return $this;
    }

    public function toArray(): array
    {
        return $this->arguments;
    }
}
