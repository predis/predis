<?php

namespace Predis\Command\Argument\Search\HybridSearch\VectorSearch;

use ValueError;

class KNNVectorSearchConfig extends BaseVectorSearchConfig
{
    /**
     * @var int
     */
    protected $k;

    /**
     * @var int
     */
    protected $ef;

    /**
     * The number of nearest neighbors to find. Defaults to 10 on server side
     *
     * @param int $k
     * @return self
     */
    public function k(int $k): self
    {
        $this->k = $k;
        return $this;
    }

    /**
     * The HNSW `ef_runtime` parameter for tuning the accuracy/speed trade-off.
     *
     * @param int $ef
     * @return $this
     */
    public function ef(int $ef): self
    {
        $this->ef = $ef;
        return $this;
    }

    public function toArray(): array
    {
        if (!$this->vector) {
            throw new ValueError('Vector configuration not specified.');
        }

        $this->arguments = array_merge($this->arguments, $this->vector);
        $this->arguments[] = 'KNN';

        $tokens = [];

        if ($this->k) {
            array_push($tokens, 'K', $this->k);
        } else {
            throw new ValueError('K is a required argument');
        }

        if ($this->ef) {
            array_push($tokens, 'EF_RUNTIME', $this->ef);
        }

        array_push($this->arguments, count($tokens), ...$tokens);

        if ($this->filter) {
            $this->arguments = array_merge($this->arguments, $this->filter);
        }

        if ($this->as) {
            $this->arguments = array_merge($this->arguments, $this->as);
        }

        return $this->arguments;
    }
}
