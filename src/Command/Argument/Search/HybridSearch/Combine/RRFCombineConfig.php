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
     * @param  int   $window
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
     * @param  int   $constant
     * @return $this
     */
    public function rrfConstant(int $constant): self
    {
        $this->rrfConstant = $constant;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function toArray(): array
    {
        $this->arguments[] = 'RRF';
        $tokens = [];

        if ($this->window !== null) {
            array_push($tokens, 'WINDOW', $this->window);
        }

        if ($this->rrfConstant !== null) {
            array_push($tokens, 'CONSTANT', $this->rrfConstant);
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
