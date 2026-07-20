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

use Predis\Command\Argument\ArrayableArgument;

class ReadArguments implements ArrayableArgument
{
    /**
     * @var array
     */
    protected $arguments = [];

    /**
     * Blocks until at least min_count qualifying samples are available or until
     * the timeout elapses.
     *
     * @param  int   $milliseconds Maximum time to wait, non-negative. 0 means wait indefinitely.
     * @param  int   $minCount     Unblock threshold, positive.
     * @return $this
     */
    public function block(int $milliseconds, int $minCount): self
    {
        array_push($this->arguments, 'BLOCK', $milliseconds, $minCount);

        return $this;
    }

    /**
     * Caps the reply at the given number of samples. When more samples qualify,
     * the oldest ones are returned so callers can page forward.
     *
     * @param  int   $maxCount Reply cap, positive.
     * @return $this
     */
    public function maxCount(int $maxCount): self
    {
        array_push($this->arguments, 'MAX_COUNT', $maxCount);

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
