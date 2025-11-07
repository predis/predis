<?php

namespace Predis\Command\Argument\Search\HybridSearch\Combine;

class LinearCombineConfig extends BaseCombine
{
    /**
     * @var float
     */
    protected $alpha;

    /**
     * @var float
     */
    protected $beta;

    /**
     * The weight for the text score (a value between 0 and 1).
     *
     * @param float $alpha
     * @return $this
     */
    public function alpha(float $alpha): self
    {
        $this->alpha = $alpha;
        return $this;
    }

    /**
     * The weight for the vector score (a value between 0 and 1).
     *
     * @param float $beta
     * @return $this
     */
    public function beta(float $beta): self
    {
        $this->beta = $beta;
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function toArray(): array
    {
        $this->arguments[] = 'LINEAR';
        $tokens = [];

        if ($this->alpha !== null) {
            array_push($tokens, 'ALPHA', $this->alpha);
        }

        if ($this->beta !== null) {
            array_push($tokens, 'BETA', $this->beta);
        }

        if ($this->as) {
            array_push($tokens, ...$this->as);
        }

        if (!empty($tokens)) {
            array_push($this->arguments, count($tokens), ...$tokens);

        }

        return $this->arguments;
    }
}
