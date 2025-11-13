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

namespace Predis\Command\Argument\Search\HybridSearch\VectorSearch;

use ValueError;

class RangeVectorSearchConfig extends BaseVectorSearchConfig
{
    /**
     * @var int
     */
    protected $radius;

    /**
     * @var float
     */
    protected $epsilon;

    /**
     * The search radius/threshold. Finds all vectors within this distance.
     *
     * @param  int   $radius
     * @return $this
     */
    public function radius(int $radius): self
    {
        $this->radius = $radius;

        return $this;
    }

    /**
     * @param  float $epsilon
     * @return $this
     */
    public function epsilon(float $epsilon): self
    {
        $this->epsilon = $epsilon;

        return $this;
    }

    public function toArray(): array
    {
        if (!$this->vector) {
            throw new ValueError('Vector configuration not specified.');
        }

        $this->arguments = array_merge($this->arguments, $this->vector);

        if ($this->radius || $this->epsilon) {
            $this->arguments[] = 'RANGE';
        }

        $tokens = [];

        if ($this->radius !== null) {
            array_push($tokens, 'RADIUS', $this->radius);
        }

        if ($this->epsilon !== null) {
            array_push($tokens, 'EPSILON', $this->epsilon);
        }

        if (!empty($tokens)) {
            array_push($this->arguments, count($tokens), ...$tokens);
        }

        if ($this->filter) {
            $this->arguments = array_merge($this->arguments, $this->filter);
        }

        if ($this->as) {
            array_push($this->arguments, ...$this->as);
        }

        return $this->arguments;
    }
}
