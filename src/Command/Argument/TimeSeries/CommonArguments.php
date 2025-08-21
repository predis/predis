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

use Predis\Command\Argument\ArrayableArgument;
use UnexpectedValueException;

class CommonArguments implements ArrayableArgument
{
    public const POLICY_BLOCK = 'BLOCK';
    public const POLICY_FIRST = 'FIRST';
    public const POLICY_LAST = 'LAST';
    public const POLICY_MIN = 'MIN';
    public const POLICY_MAX = 'MAX';
    public const POLICY_SUM = 'SUM';

    public const ENCODING_UNCOMPRESSED = 'UNCOMPRESSED';
    public const ENCODING_COMPRESSED = 'COMPRESSED';

    /**
     * @var array
     */
    protected $arguments = [];

    /**
     * Is maximum age for samples compared to the highest reported timestamp, in milliseconds.
     *
     * @param  int   $retentionPeriod
     * @return $this
     */
    public function retentionMsecs(int $retentionPeriod): self
    {
        array_push($this->arguments, 'RETENTION', $retentionPeriod);

        return $this;
    }

    /**
     * Ignore samples with given time or value difference.
     *
     * @param  int   $maxTimeDiff Non-negative integer value in milliseconds
     * @param  float $maxValDiff  Non-negative float value
     * @return $this
     */
    public function ignore(int $maxTimeDiff, float $maxValDiff): self
    {
        if ($maxTimeDiff < 0 || $maxValDiff < 0) {
            throw new UnexpectedValueException('Ignore does not accept negative values');
        }

        array_push($this->arguments, 'IGNORE', $maxTimeDiff, $maxValDiff);

        return $this;
    }

    /**
     * Is initial allocation size, in bytes, for the data part of each new chunk.
     *
     * @param  int   $size
     * @return $this
     */
    public function chunkSize(int $size): self
    {
        array_push($this->arguments, 'CHUNK_SIZE', $size);

        return $this;
    }

    /**
     * Is policy for handling insertion of multiple samples with identical timestamps.
     *
     * @param  string $policy
     * @return $this
     */
    public function duplicatePolicy(string $policy = self::POLICY_BLOCK): self
    {
        array_push($this->arguments, 'DUPLICATE_POLICY', $policy);

        return $this;
    }

    /**
     * Is set of label-value pairs that represent metadata labels of the key and serve as a secondary index.
     *
     * @param  mixed ...$labelValuePair
     * @return $this
     */
    public function labels(...$labelValuePair): self
    {
        array_push($this->arguments, 'LABELS', ...$labelValuePair);

        return $this;
    }

    /**
     * Specifies the series samples encoding format.
     *
     * @param  string $encoding
     * @return $this
     */
    public function encoding(string $encoding = self::ENCODING_COMPRESSED): self
    {
        array_push($this->arguments, 'ENCODING', $encoding);

        return $this;
    }

    /**
     * Is used when a time series is a compaction.
     * With LATEST, TS.GET reports the compacted value of the latest, possibly partial, bucket.
     *
     * @return $this
     */
    public function latest(): self
    {
        $this->arguments[] = 'LATEST';

        return $this;
    }

    /**
     * Includes in the reply all label-value pairs representing metadata labels of the time series.
     *
     * @return $this
     */
    public function withLabels(): self
    {
        $this->arguments[] = 'WITHLABELS';

        return $this;
    }

    /**
     * Returns a subset of the label-value pairs that represent metadata labels of the time series.
     *
     * @return $this
     */
    public function selectedLabels(string ...$labels): self
    {
        array_push($this->arguments, 'SELECTED_LABELS', ...$labels);

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function toArray(): array
    {
        return $this->arguments;
    }
}
