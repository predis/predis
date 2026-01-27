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

namespace Predis\Command\Argument\TimeSeries;

class RangeArguments extends CommonArguments
{
    public const AGG_SUM = 'sum';
    public const AGG_MIN = 'min';
    public const AGG_MAX = 'max';
    public const AGG_COUNT = 'count';
    public const AGG_COUNT_NAN = 'countNan';
    public const AGG_COUNT_ALL = 'countAll';

    /**
     * Filters samples by a list of specific timestamps.
     *
     * @param  int   ...$ts
     * @return $this
     */
    public function filterByTs(int ...$ts): self
    {
        array_push($this->arguments, 'FILTER_BY_TS', ...$ts);

        return $this;
    }

    /**
     * Filters samples by minimum and maximum values.
     *
     * @param  int   $min
     * @param  int   $max
     * @return $this
     */
    public function filterByValue(int $min, int $max): self
    {
        array_push($this->arguments, 'FILTER_BY_VALUE', $min, $max);

        return $this;
    }

    /**
     * Limits the number of returned samples.
     *
     * @param  int   $count
     * @return $this
     */
    public function count(int $count): self
    {
        array_push($this->arguments, 'COUNT', $count);

        return $this;
    }

    /**
     * Aggregates samples into time buckets.
     *
     * @param  string $aggregator      Aggregation type. Check class constants.
     * @param  int    $bucketDuration  Is duration of each bucket, in milliseconds.
     * @param  int    $align           It controls the time bucket timestamps by changing the reference timestamp on which a bucket is defined.
     * @param  int    $bucketTimestamp Controls how bucket timestamps are reported.
     * @param  bool   $empty           Is a flag, which, when specified, reports aggregations also for empty buckets.
     * @return $this
     */
    public function aggregation(string $aggregator, int $bucketDuration, int $align = 0, int $bucketTimestamp = 0, bool $empty = false): self
    {
        if ($align > 0) {
            array_push($this->arguments, 'ALIGN', $align);
        }

        array_push($this->arguments, 'AGGREGATION', $aggregator, $bucketDuration);

        if ($bucketTimestamp > 0) {
            array_push($this->arguments, 'BUCKETTIMESTAMP', $bucketTimestamp);
        }

        if (true === $empty) {
            $this->arguments[] = 'EMPTY';
        }

        return $this;
    }
}
