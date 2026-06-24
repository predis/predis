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
use UnexpectedValueException;

class BGetArguments implements ArrayableArgument
{
    /**
     * @var array
     */
    protected $arguments = [];

    /**
     * Blocks until at least this number of qualifying samples is available.
     *
     * @param  int   $count Positive integer
     * @return $this
     */
    public function minCount(int $count): self
    {
        if ($count < 1) {
            throw new UnexpectedValueException('Min count should be a positive integer');
        }

        array_push($this->arguments, 'MIN_COUNT', $count);

        return $this;
    }

    /**
     * Returns up to this number of samples.
     *
     * @param  int   $count Positive integer
     * @return $this
     */
    public function maxCount(int $count): self
    {
        if ($count < 1) {
            throw new UnexpectedValueException('Max count should be a positive integer');
        }

        array_push($this->arguments, 'MAX_COUNT', $count);

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
