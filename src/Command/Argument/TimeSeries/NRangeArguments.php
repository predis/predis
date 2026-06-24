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

class NRangeArguments extends RangeArguments
{
    /**
     * Aggregates samples into time buckets.
     *
     * Unlike TS.RANGE, TS.NRANGE expects one aggregator per queried key, passed
     * as individual tokens (e.g. AGGREGATION min max 1000) rather than a single
     * comma-separated token. Exactly numkeys aggregators are required.
     *
     * @param  string|array $aggregator      Aggregation type, or list of aggregation types. Check class constants.
     * @param  int          $bucketDuration  Is duration of each bucket, in milliseconds.
     * @param  int          $align           It controls the time bucket timestamps by changing the reference timestamp on which a bucket is defined.
     * @param  int          $bucketTimestamp Controls how bucket timestamps are reported.
     * @param  bool         $empty           Is a flag, which, when specified, reports aggregations also for empty buckets.
     * @return $this
     */
    public function aggregation($aggregator, int $bucketDuration, int $align = 0, int $bucketTimestamp = 0, bool $empty = false): RangeArguments
    {
        $aggregators = is_array($aggregator) ? $aggregator : explode(',', (string) $aggregator);

        if ($align > 0) {
            array_push($this->arguments, 'ALIGN', $align);
        }

        array_push($this->arguments, 'AGGREGATION', ...$aggregators, ...[$bucketDuration]);

        if ($bucketTimestamp > 0) {
            array_push($this->arguments, 'BUCKETTIMESTAMP', $bucketTimestamp);
        }

        if (true === $empty) {
            $this->arguments[] = 'EMPTY';
        }

        return $this;
    }
}
