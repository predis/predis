<?php

/*
 * This file is part of the Predis package.
 *
 * (c) 2009-2020 Daniele Alessandri
 * (c) 2021-2026 Till Krüss
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Command\Argument\TimeSeries;

use UnexpectedValueException;

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
     * {@inheritDoc}
     *
     * Multiple aggregators cannot be combined with GROUPBY.
     *
     * @return $this
     */
    public function aggregation($aggregator, int $bucketDuration, int $align = 0, int $bucketTimestamp = 0, bool $empty = false): RangeArguments
    {
        $isMulti = is_array($aggregator) ? count($aggregator) > 1 : strpos((string) $aggregator, ',') !== false;

        if ($isMulti && in_array('GROUPBY', $this->arguments, true)) {
            throw new UnexpectedValueException('Multiple aggregators cannot be combined with GROUPBY.');
        }

        return parent::aggregation($aggregator, $bucketDuration, $align, $bucketTimestamp, $empty);
    }

    /**
     * Splits time series into groups, each group contains time series that share the same
     * value for the provided label name, then aggregates results in each group.
     *
     * GROUPBY cannot be combined with multiple aggregators set via aggregation().
     *
     * @param  string $label
     * @param  string $reducer
     * @return $this
     */
    public function groupBy(string $label, string $reducer): self
    {
        $aggIndex = array_search('AGGREGATION', $this->arguments, true);

        if ($aggIndex !== false
            && isset($this->arguments[$aggIndex + 1])
            && is_string($this->arguments[$aggIndex + 1])
            && strpos($this->arguments[$aggIndex + 1], ',') !== false
        ) {
            throw new UnexpectedValueException('GROUPBY cannot be combined with multiple aggregators.');
        }

        array_push($this->arguments, 'GROUPBY', $label, 'REDUCE', $reducer);

        return $this;
    }
}
