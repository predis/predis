<?php

/*
 * This file is part of the Predis package.
 *
 * (c) 2009-2020 Daniele Alessandri
 * (c) 2021-2023 Till Krüss
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Command\Argument\TimeSeries;

use Predis\Command\Argument\ArrayableArgument;

class GetArguments implements ArrayableArgument
{
    /**
     * @var array
     */
    protected $arguments = [];

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
     * {@inheritDoc}
     */
    public function toArray(): array
    {
        return $this->arguments;
    }
}
