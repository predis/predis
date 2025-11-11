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

use Predis\Command\Argument\ArrayableArgument;

abstract class BaseCombine implements ArrayableArgument
{
    /**
     * @var array
     */
    protected $arguments = ['COMBINE'];

    /**
     * @var array
     */
    protected $as = [];

    /**
     * @param  string $alias
     * @return $this
     */
    public function as(string $alias): self
    {
        array_push($this->as, 'YIELD_SCORE_AS', $alias);

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    abstract public function toArray(): array;
}
