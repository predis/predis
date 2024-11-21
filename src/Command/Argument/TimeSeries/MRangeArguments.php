<?php

/*
 * This file is part of the Predis package.
 *
 * (c) 2009-2020 Daniele Alessandri
 * (c) 2021-2024 Till Krüss
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Command\Argument\TimeSeries;

class MRangeArguments extends RangeArguments
{
    /**
     * Filters time series based on their labels and label values.
     *
     * @param  string ...$filterExpressions
     * @return $this
     */
    public function filter(string ...$filterExpressions): self
    {
        array_push($this->arguments, 'FILTER', ...$filterExpressions);

        return $this;
    }

    /**
     * Splits time series into groups, each group contains time series that share the same
     * value for the provided label name, then aggregates results in each group.
     *
     * @param  string $label
     * @param  string $reducer
     * @return $this
     */
    public function groupBy(string $label, string $reducer): self
    {
        array_push($this->arguments, 'GROUPBY', $label, 'REDUCE', $reducer);

        return $this;
    }
}
