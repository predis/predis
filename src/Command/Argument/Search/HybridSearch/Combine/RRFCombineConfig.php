<?php

namespace Predis\Command\Argument\Search\HybridSearch\Combine;

class RRFCombineConfig extends BaseCombine
{
    /**
     * @var int
     */
    protected $window;

    /**
     * @var int
     */
    protected $rrfConstant;

    /**
     * The number of top results from each search type to consider for fusion. Defaults to 50.
     *
     * @param int $window
     * @return $this
     */
    public function window(int $window): self
    {
        $this->window = $window;
        return $this;
    }

    /**
     * The RRF ranking constant. A smaller value gives more weight to top-ranked items. Defaults to 60.
     *
     * @param int $constant
     * @return void
     */
    public function rrfConstant(int $constant): self
    {
        $this->rrfConstant = $constant;
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function toArray(): array
    {
        $this->arguments[] = 'RRF';
        $tokens = [];

        if ($this->window) {
            array_push($tokens, 'WINDOW', $this->window);
        }

        if ($this->rrfConstant) {
            array_push($tokens, 'CONSTANT', $this->rrfConstant);
        }

        if (!empty($tokens)) {
            array_push($this->arguments, count($tokens), ...$tokens);
        }

        return $this->arguments;
    }
}
